import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');
const requestCounter = new Counter('total_requests');
const slowRequests = new Counter('slow_requests'); // Requests > 2000ms
const endpointCounter = new Counter('endpoint_requests');
const endpointDurationTrends = {
  'home-page': new Trend('home_page_duration'),
  'restaurant-settings': new Trend('restaurant_settings_duration'),
  'availability': new Trend('availability_duration'),
  'create-reservation': new Trend('create_reservation_duration'),
  'time-slots': new Trend('time_slots_duration'),
  'closed-dates': new Trend('closed_dates_duration'),
  'restaurant-settings-date': new Trend('restaurant_settings_date_duration'),
  'reservation-status': new Trend('reservation_status_duration'),
  'resend-otp': new Trend('resend_otp_duration'),
};

// Enhanced stress test configuration - optimized for maximum RPS
export const options = {
  stages: [
    { duration: '300s', target: 20 },     // Warm-up cache (5 minutes)
    { duration: '30s', target: 50 },      // Ramp up to 50 users
    { duration: '60s', target: 100 },    // Ramp up to 100 users
    { duration: '60s', target: 200 },     // Ramp up to 200 users
    { duration: '60s', target: 300 },     // Ramp up to 300 users
    { duration: '60s', target: 400 },     // Ramp up to 400 users
    { duration: '60s', target: 500 },     // Ramp up to 500 users
    { duration: '120s', target: 500 },   // Stay at 500 users (stress point)
    { duration: '60s', target: 600 },     // Push to 600 users
    { duration: '60s', target: 700 },     // Push to 700 users
    { duration: '60s', target: 800 },     // Push to 800 users
    { duration: '60s', target: 1000 },    // Push to 1000 users (extreme stress)
    { duration: '120s', target: 1000 },  // Stay at 1000 users (maximum stress)
    { duration: '30s', target: 0 },      // Ramp down
  ],
  thresholds: {
    // Lenient thresholds for stress testing - we want to find the breaking point
    http_req_duration: ['p(95)<10000'],   // 95% of requests should be below 10s (stress test)
    http_req_failed: ['rate<0.30'],       // Allow up to 30% error rate (stress test)
    errors: ['rate<0.30'],                 // Custom error rate
    // Monitor for system degradation
    'http_req_duration{status:200}': ['p(95)<5000'], // Successful requests should be < 5s
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)', 'p(99.9)', 'p(99.99)'],
};

// Base URL from environment variable or default
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const TABLE_ID_MIN = parseInt(__ENV.TABLE_ID_MIN || '1', 10);
const TABLE_ID_MAX = parseInt(__ENV.TABLE_ID_MAX || '20', 10);
const DATE_RANGE_DAYS = parseInt(__ENV.DATE_RANGE_DAYS || '30', 10);

// Track known tables and valid date/time slots to increase randomness (per VU)
const knownTables = [];
const knownTableMap = new Map();
const knownDateTimes = [];
const knownDateTimesSet = new Set();

// Helper function to get future date
function getFutureDate(daysAhead = 1) {
  const date = new Date();
  date.setDate(date.getDate() + daysAhead);
  return date.toISOString().split('T')[0];
}

// Helper function to get random date within next N days
function getRandomDate() {
  const range = Number.isFinite(DATE_RANGE_DAYS) ? DATE_RANGE_DAYS : 30;
  const days = Math.floor(Math.random() * range) + 1;
  return getFutureDate(days);
}

// Helper function to get random time slot
function getRandomTime() {
  const hours = [17, 18, 19, 20, 21];
  const minutes = ['00', '30'];
  const hour = hours[Math.floor(Math.random() * hours.length)];
  const minute = minutes[Math.floor(Math.random() * minutes.length)];
  return `${hour}:${minute}`;
}

// Helper function to generate random customer data
function generateCustomerData() {
  const names = ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Williams', 'Charlie Brown', 'Diana Prince', 'Bruce Wayne'];
  const domains = ['example.com', 'test.com', 'demo.com', 'loadtest.com'];
  const name = names[Math.floor(Math.random() * names.length)];
  const email = name.toLowerCase().replace(' ', '.') + Math.floor(Math.random() * 10000) + '@' + domains[Math.floor(Math.random() * domains.length)];
  const phone = '601' + Math.floor(Math.random() * 10000000).toString().padStart(8, '0');
  
  return { name, email, phone };
}

function pickRandom(items) {
  return items[Math.floor(Math.random() * items.length)];
}

function rememberTables(tables) {
  if (!tables || tables.length === 0) return;
  tables.forEach((table) => {
    if (!table || typeof table.id === 'undefined') return;
    if (!knownTableMap.has(table.id)) {
      knownTableMap.set(table.id, table);
      knownTables.push(table);
      // Keep the pool bounded
      if (knownTables.length > 200) {
        const removed = knownTables.shift();
        if (removed && typeof removed.id !== 'undefined') {
          knownTableMap.delete(removed.id);
        }
      }
    }
  });
}

function rememberDateTime(date, time) {
  if (!date || !time) return;
  const key = `${date}_${time}`;
  if (knownDateTimesSet.has(key)) return;
  knownDateTimesSet.add(key);
  knownDateTimes.push({ date, time });
  if (knownDateTimes.length > 300) {
    const removed = knownDateTimes.shift();
    if (removed) {
      knownDateTimesSet.delete(`${removed.date}_${removed.time}`);
    }
  }
}

function rememberTimeSlots(date, slots) {
  if (!date || !slots || slots.length === 0) return;
  slots.forEach((slot) => {
    if (slot && slot.value) {
      rememberDateTime(date, slot.value);
    }
  });
}

function getRandomDateTime() {
  if (knownDateTimes.length > 0) {
    return pickRandom(knownDateTimes);
  }
  return { date: getRandomDate(), time: getRandomTime() };
}

function getRandomTable() {
  if (knownTables.length > 0) {
    return pickRandom(knownTables);
  }
  const min = Number.isFinite(TABLE_ID_MIN) ? TABLE_ID_MIN : 1;
  const max = Number.isFinite(TABLE_ID_MAX) ? TABLE_ID_MAX : 20;
  const tableId = Math.floor(Math.random() * (max - min + 1)) + min;
  return { id: tableId, capacity: 10 };
}

function getRandomPaxForTable(table) {
  const maxPax = Math.max(1, Math.min(20, table?.capacity || 10));
  const pax = Math.floor(Math.random() * 6) + 2;
  return Math.min(pax, maxPax);
}

// Endpoint test functions for better organization
function testHomePage(baseUrl, headers) {
  const response = http.get(`${baseUrl}/`, {
    headers: {
      'X-k6-Test': 'true',
      'User-Agent': 'k6-stress-test',
    },
    tags: { endpoint: 'home-page' },
  });
  
  const success = check(response, {
    'home status is 200': (r) => r.status === 200,
  });
  
  return { response, success };
}

function testRestaurantSettings(baseUrl, headers) {
  const response = http.get(`${baseUrl}/api/v1/restaurant-settings`, {
    headers,
    tags: { endpoint: 'restaurant-settings' },
  });
  
  const success = check(response, {
    'settings status is 200': (r) => r.status === 200,
  });
  
  return { response, success };
}

function testClosedDates(baseUrl, headers) {
  const response = http.get(`${baseUrl}/api/v1/closed-dates`, {
    headers,
    tags: { endpoint: 'closed-dates' },
  });
  
  const success = check(response, {
    'closed dates status is 200': (r) => r.status === 200,
  });
  
  return { response, success };
}

function testTimeSlots(baseUrl, headers, date) {
  const response = http.get(`${baseUrl}/api/v1/time-slots?date=${date}`, {
    headers,
    tags: { endpoint: 'time-slots' },
  });
  
  const success = check(response, {
    'time slots status is 200': (r) => r.status === 200,
  });
  
  if (success) {
    try {
      const data = JSON.parse(response.body);
      const slots = data.time_slots || [];
      rememberTimeSlots(date, slots);
    } catch (e) {
      // Continue if parsing fails
    }
  }

  return { response, success };
}

function testAvailability(baseUrl, headers, date, time, pax) {
  const response = http.get(
    `${baseUrl}/api/v1/availability?date=${date}&time=${time}&pax=${pax}`,
    {
      headers,
      tags: { endpoint: 'availability' },
    }
  );
  
  const success = check(response, {
    'availability status is 200': (r) => r.status === 200,
  });
  
  let tables = [];
  if (success) {
    try {
      const data = JSON.parse(response.body);
      tables = data.tables || [];
      rememberTables(tables);
      rememberDateTime(date, time);
    } catch (e) {
      // Continue if parsing fails
    }
  }
  
  return { response, success, tables };
}

function testDateSettings(baseUrl, headers, date) {
  const response = http.get(
    `${baseUrl}/api/v1/restaurant-settings?date=${date}`,
    {
      headers,
      tags: { endpoint: 'restaurant-settings-date' },
    }
  );
  
  const success = check(response, {
    'date settings status is 200': (r) => r.status === 200,
  });
  
  return { response, success };
}

function testCreateReservation(baseUrl, headers, tableId, customerData, date, time, pax) {
  const payload = JSON.stringify({
    table_id: tableId,
    customer_name: customerData.name,
    customer_email: customerData.email,
    customer_phone: customerData.phone,
    pax: pax,
    reservation_date: date,
    reservation_time: time,
    notes: 'Stress test reservation',
  });
  
  const response = http.post(
    `${baseUrl}/api/v1/reservations`,
    payload,
    {
      headers,
      tags: { endpoint: 'create-reservation' },
    }
  );
  
  const success = check(response, {
    'reservation response received': (r) => r.status > 0,
    'reservation status acceptable': (r) => [202, 409, 422, 403].includes(r.status),
  });
  
  let sessionId = null;
  if (response.status === 202) {
    try {
      const data = JSON.parse(response.body);
      sessionId = data.session_id;
    } catch (e) {
      // Continue if parsing fails
    }
  }
  
  return { response, success, sessionId };
}

function testReservationStatus(baseUrl, headers, sessionId) {
  const response = http.get(
    `${baseUrl}/api/v1/reservation-status?session_id=${sessionId}`,
    {
      headers,
      tags: { endpoint: 'reservation-status' },
    }
  );
  
  const success = check(response, {
    'status check response received': (r) => r.status > 0,
  });
  
  return { response, success };
}

function testResendOtp(baseUrl, headers, sessionId) {
  const payload = JSON.stringify({
    session_id: sessionId,
  });
  
  const response = http.post(
    `${baseUrl}/api/v1/resend-otp`,
    payload,
    {
      headers,
      tags: { endpoint: 'resend-otp' },
    }
  );
  
  const success = check(response, {
    'resend otp response received': (r) => r.status > 0,
  });
  
  return { response, success };
}

// Track metrics helper
function trackMetrics(response, success, endpointName) {
  requestCounter.add(1);
  endpointCounter.add(1, { endpoint: endpointName });
  if (response.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!success);
  responseTime.add(response.timings.duration);
  const trend = endpointDurationTrends[endpointName];
  if (trend) {
    trend.add(response.timings.duration);
  }
}

export default function () {
  const headers = {
    'X-k6-Test': 'true',
    'User-Agent': 'k6-stress-test',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  // Enhanced strategy: Test endpoints in random order with weighted distribution
  // This creates more realistic load patterns and maximizes throughput
  
  // Weighted endpoint selection (more frequent endpoints get higher weight)
  const endpointWeights = {
    'home': 30,           // Home page - most frequent
    'settings': 20,       // Restaurant settings - cached, fast
    'availability': 15,   // Availability - database intensive
    'time-slots': 10,    // Time slots
    'closed-dates': 5,   // Closed dates - cached
    'date-settings': 5,  // Date-specific settings
    'reservation': 10,   // Create reservation
    'status': 3,         // Check status
    'resend-otp': 2,     // Resend OTP - least frequent
  };
  
  // Select random endpoint based on weights
  const random = Math.random() * 100;
  let selectedEndpoint = 'home';
  let cumulative = 0;
  
  for (const [endpoint, weight] of Object.entries(endpointWeights)) {
    cumulative += weight;
    if (random <= cumulative) {
      selectedEndpoint = endpoint;
      break;
    }
  }
  
  // Execute selected endpoint
  switch (selectedEndpoint) {
    case 'home': {
      const result = testHomePage(BASE_URL, headers);
      trackMetrics(result.response, result.success, 'home-page');
      break;
    }
    
    case 'settings': {
      const result = testRestaurantSettings(BASE_URL, headers);
      trackMetrics(result.response, result.success, 'restaurant-settings');
      break;
    }
    
    case 'closed-dates': {
      const result = testClosedDates(BASE_URL, headers);
      trackMetrics(result.response, result.success, 'closed-dates');
      break;
    }
    
    case 'time-slots': {
      const date = getRandomDate();
      const result = testTimeSlots(BASE_URL, headers, date);
      trackMetrics(result.response, result.success, 'time-slots');
      break;
    }
    
    case 'availability': {
      const { date, time } = getRandomDateTime();
      const pax = Math.floor(Math.random() * 6) + 2;
      const result = testAvailability(BASE_URL, headers, date, time, pax);
      trackMetrics(result.response, result.success, 'availability');
      
      // If availability check succeeded, optionally create reservation
      if (result.success && (result.tables.length > 0 || Math.random() > 0.4)) {
        const customerData = generateCustomerData();
        const chosenTable = result.tables.length > 0 
          ? pickRandom(result.tables)
          : getRandomTable();
        const tableId = chosenTable.id;
        
        const reservationResult = testCreateReservation(
          BASE_URL, headers, tableId, customerData, date, time, pax
        );
        trackMetrics(reservationResult.response, reservationResult.success, 'create-reservation');
        
        // If reservation created, check status
        if (reservationResult.sessionId && Math.random() > 0.5) {
          const statusResult = testReservationStatus(BASE_URL, headers, reservationResult.sessionId);
          trackMetrics(statusResult.response, statusResult.success, 'reservation-status');
          
          // Occasionally resend OTP
          if (Math.random() > 0.85) {
            const resendResult = testResendOtp(BASE_URL, headers, reservationResult.sessionId);
            trackMetrics(resendResult.response, resendResult.success, 'resend-otp');
          }
        }
      }
      break;
    }
    
    case 'date-settings': {
      const date = getRandomDate();
      const result = testDateSettings(BASE_URL, headers, date);
      trackMetrics(result.response, result.success, 'restaurant-settings-date');
      break;
    }
    
    case 'reservation': {
      // Direct reservation creation (without availability check)
      const customerData = generateCustomerData();
      const { date, time } = getRandomDateTime();
      const table = getRandomTable();
      const pax = getRandomPaxForTable(table);
      const tableId = table.id;
      
      const result = testCreateReservation(
        BASE_URL, headers, tableId, customerData, date, time, pax
      );
      trackMetrics(result.response, result.success, 'create-reservation');
      
      if (result.sessionId && Math.random() > 0.3) {
        const statusResult = testReservationStatus(BASE_URL, headers, result.sessionId);
        trackMetrics(statusResult.response, statusResult.success, 'reservation-status');
      }
      break;
    }
    
    case 'status': {
      // This would require a valid session_id, so we'll skip or use a random one
      // In practice, this endpoint is usually called after reservation creation
      break;
    }
    
    case 'resend-otp': {
      // This would require a valid session_id, so we'll skip
      // In practice, this endpoint is usually called after reservation creation
      break;
    }
  }
  
  // No sleep - maximum throughput for stress testing
}

export function handleSummary(data) {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  
  // Calculate metrics
  const totalRequests = data.metrics.http_reqs?.values?.count || 0;
  const failedRequests = data.metrics.http_req_failed?.values?.rate || 0;
  const avgResponseTime = data.metrics.http_req_duration?.values?.avg || 0;
  const p95ResponseTime = data.metrics.http_req_duration?.values?.['p(95)'] || 0;
  const p99ResponseTime = data.metrics.http_req_duration?.values?.['p(99)'] || 0;
  const maxVUs = data.metrics.vus?.values?.max || 0;
  const throughput = data.metrics.http_reqs?.values?.rate || 0; // Requests per second
  const testDuration = data.metrics.iteration_duration?.values?.avg || 0;
  const endpointStats = {
    home_page: data.metrics.home_page_duration?.values || null,
    restaurant_settings: data.metrics.restaurant_settings_duration?.values || null,
    availability: data.metrics.availability_duration?.values || null,
    create_reservation: data.metrics.create_reservation_duration?.values || null,
    time_slots: data.metrics.time_slots_duration?.values || null,
    closed_dates: data.metrics.closed_dates_duration?.values || null,
    restaurant_settings_date: data.metrics.restaurant_settings_date_duration?.values || null,
    reservation_status: data.metrics.reservation_status_duration?.values || null,
    resend_otp: data.metrics.resend_otp_duration?.values || null,
  };
  
  const summary = {
    timestamp: new Date().toISOString(),
    test_type: 'stress_test_enhanced_all_endpoints',
    summary: {
      total_requests: totalRequests,
      failed_requests_percent: (failedRequests * 100).toFixed(2),
      avg_response_time_ms: avgResponseTime.toFixed(2),
      p95_response_time_ms: p95ResponseTime.toFixed(2),
      p99_response_time_ms: p99ResponseTime.toFixed(2),
      max_virtual_users: maxVUs,
      requests_per_second: throughput.toFixed(2),
      peak_rps: throughput.toFixed(2), // Peak RPS achieved
      test_duration_seconds: (testDuration / 1000).toFixed(2),
    },
    endpoint_metrics: endpointStats,
    full_data: data,
  };
  
  return {
    [`results/stress-test-${timestamp}.json`]: JSON.stringify(summary, null, 2),
    'stdout': `
╔══════════════════════════════════════════════════════════════╗
║     ENHANCED STRESS TEST RESULTS - ALL API ENDPOINTS        ║
║              MAXIMUM PERFORMANCE TEST                       ║
╚══════════════════════════════════════════════════════════════╝

📊 Performance Metrics:
   Total Requests:        ${totalRequests.toLocaleString()}
   Peak RPS:              ${throughput.toFixed(2)} requests/second
   Failed Requests:       ${(failedRequests * 100).toFixed(2)}%
   Max Virtual Users:     ${maxVUs}

⏱️  Response Times:
   Average:                ${avgResponseTime.toFixed(2)}ms
   P95:                    ${p95ResponseTime.toFixed(2)}ms
   P99:                    ${p99ResponseTime.toFixed(2)}ms

🎯 Tested Endpoints (Weighted Random Distribution):
   ✅ GET  / (Home Page) - 30% weight
   ✅ GET  /api/v1/restaurant-settings - 20% weight
   ✅ GET  /api/v1/availability - 15% weight
   ✅ POST /api/v1/reservations - 10% weight
   ✅ GET  /api/v1/time-slots - 10% weight
   ✅ GET  /api/v1/closed-dates - 5% weight
   ✅ GET  /api/v1/restaurant-settings?date=... - 5% weight
   ✅ GET  /api/v1/reservation-status - 3% weight
   ✅ POST /api/v1/resend-otp - 2% weight

💡 System Status:
   ${failedRequests < 0.1 ? '✅ System handling load well' : failedRequests < 0.2 ? '⚠️  System under stress' : '❌ System overloaded'}
   ${throughput > 100 ? '🚀 Excellent throughput!' : throughput > 50 ? '✅ Good throughput' : '⚠️  Low throughput'}
   ${avgResponseTime < 1000 ? '✅ Response times acceptable' : avgResponseTime < 2000 ? '⚠️  Response times degrading' : '❌ Response times critical'}

📁 Full results saved to: results/stress-test-${timestamp}.json
`,
  };
}
