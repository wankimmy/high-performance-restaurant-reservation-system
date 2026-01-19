import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');
const requestCounter = new Counter('total_requests');
const slowRequests = new Counter('slow_requests'); // Requests > 2000ms
const rpsCounter = new Counter('requests_per_second');

// Stress test configuration - push ALL API endpoints to maximum capacity
export const options = {
  stages: [
    { duration: '30s', target: 50 },      // Ramp up to 50 users
    { duration: '60s', target: 100 },    // Ramp up to 100 users
    { duration: '60s', target: 200 },    // Ramp up to 200 users
    { duration: '60s', target: 300 },    // Ramp up to 300 users
    { duration: '60s', target: 400 },    // Ramp up to 400 users
    { duration: '60s', target: 500 },    // Ramp up to 500 users
    { duration: '120s', target: 500 },   // Stay at 500 users (stress point)
    { duration: '60s', target: 600 },    // Push to 600 users
    { duration: '60s', target: 700 },    // Push to 700 users
    { duration: '60s', target: 800 },    // Push to 800 users
    { duration: '60s', target: 1000 },   // Push to 1000 users (extreme stress)
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

// Helper function to get future date
function getFutureDate(daysAhead = 1) {
  const date = new Date();
  date.setDate(date.getDate() + daysAhead);
  return date.toISOString().split('T')[0];
}

// Helper function to get random date within next 7 days
function getRandomDate() {
  const days = Math.floor(Math.random() * 7) + 1;
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


export default function () {
  const headers = {
    'X-k6-Test': 'true',
    'User-Agent': 'k6-stress-test',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  // Test ALL API endpoints in random order to maximize load
  
  // 0. GET / (Home Page - most frequent)
  const homeResponse = http.get(`${BASE_URL}/`, {
    headers: {
      'X-k6-Test': 'true',
      'User-Agent': 'k6-stress-test',
    },
    tags: { endpoint: 'home-page' },
  });

  const homeCheck = check(homeResponse, {
    'home status is 200': (r) => r.status === 200,
    'home response received': (r) => r.status > 0,
  });

  requestCounter.add(1);
  if (homeResponse.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!homeCheck);
  responseTime.add(homeResponse.timings.duration);
  sleep(Math.random() * 0.1 + 0.05);

  // 1. GET /api/v1/restaurant-settings (most frequent - cached)
  const settingsResponse = http.get(`${BASE_URL}/api/v1/restaurant-settings`, {
    headers,
    tags: { endpoint: 'restaurant-settings' },
  });

  const settingsCheck = check(settingsResponse, {
    'settings status is 200': (r) => r.status === 200,
    'settings response received': (r) => r.status > 0,
  });

  requestCounter.add(1);
  if (settingsResponse.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!settingsCheck);
  responseTime.add(settingsResponse.timings.duration);
  sleep(Math.random() * 0.1 + 0.05);

  // 2. GET /api/v1/closed-dates
  const closedDatesResponse = http.get(`${BASE_URL}/api/v1/closed-dates`, {
    headers,
    tags: { endpoint: 'closed-dates' },
  });

  const closedDatesCheck = check(closedDatesResponse, {
    'closed dates status is 200': (r) => r.status === 200,
    'closed dates response received': (r) => r.status > 0,
  });

  requestCounter.add(1);
  if (closedDatesResponse.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!closedDatesCheck);
  responseTime.add(closedDatesResponse.timings.duration);
  sleep(Math.random() * 0.1 + 0.05);

  // 3. GET /api/v1/time-slots (with random date)
  const dateForSlots = getRandomDate();
  const timeSlotsResponse = http.get(`${BASE_URL}/api/v1/time-slots?date=${dateForSlots}`, {
    headers,
    tags: { endpoint: 'time-slots' },
  });

  const timeSlotsCheck = check(timeSlotsResponse, {
    'time slots status is 200': (r) => r.status === 200,
    'time slots response received': (r) => r.status > 0,
  });

  requestCounter.add(1);
  if (timeSlotsResponse.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!timeSlotsCheck);
  responseTime.add(timeSlotsResponse.timings.duration);
  sleep(Math.random() * 0.1 + 0.05);

  // 4. GET /api/v1/availability (high frequency - database intensive)
  const availabilityDate = getRandomDate();
  const availabilityTime = getRandomTime();
  const pax = Math.floor(Math.random() * 6) + 2; // 2-7 people
  const availabilityResponse = http.get(
    `${BASE_URL}/api/v1/availability?date=${availabilityDate}&time=${availabilityTime}&pax=${pax}`,
    {
      headers,
      tags: { endpoint: 'availability' },
    }
  );

  const availabilityCheck = check(availabilityResponse, {
    'availability status is 200': (r) => r.status === 200,
    'availability response received': (r) => r.status > 0,
  });

  requestCounter.add(1);
  if (availabilityResponse.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!availabilityCheck);
  responseTime.add(availabilityResponse.timings.duration);
  sleep(Math.random() * 0.1 + 0.05);

  // 5. GET /api/v1/restaurant-settings?date=... (date-specific)
  const dateSettingsResponse = http.get(
    `${BASE_URL}/api/v1/restaurant-settings?date=${getRandomDate()}`,
    {
      headers,
      tags: { endpoint: 'restaurant-settings-date' },
    }
  );

  const dateSettingsCheck = check(dateSettingsResponse, {
    'date settings status is 200': (r) => r.status === 200,
    'date settings response received': (r) => r.status > 0,
  });

  requestCounter.add(1);
  if (dateSettingsResponse.timings.duration > 2000) slowRequests.add(1);
  errorRate.add(!dateSettingsCheck);
  responseTime.add(dateSettingsResponse.timings.duration);
  sleep(Math.random() * 0.1 + 0.05);

  // 6. POST /api/v1/reservations (creates load on database and queue)
  // Extract available tables from availability response
  let availableTables = [];
  if (availabilityCheck) {
    try {
      const data = JSON.parse(availabilityResponse.body);
      availableTables = data.tables || [];
    } catch (e) {
      // Continue even if parsing fails
    }
  }

  // Attempt reservation creation (even if no tables available, to test error handling)
  if (availableTables.length > 0 || Math.random() > 0.3) {
    const customerData = generateCustomerData();
    const reservationDate = getRandomDate();
    const reservationTime = getRandomTime();
    const reservationPax = Math.floor(Math.random() * 6) + 2;
    const tableId = availableTables.length > 0 
      ? availableTables[0].id 
      : Math.floor(Math.random() * 10) + 1; // Random table ID if none available

    const reservationPayload = JSON.stringify({
      table_id: tableId,
      customer_name: customerData.name,
      customer_email: customerData.email,
      customer_phone: customerData.phone,
      pax: reservationPax,
      reservation_date: reservationDate,
      reservation_time: reservationTime,
      notes: 'Stress test reservation',
    });

    const reservationResponse = http.post(
      `${BASE_URL}/api/v1/reservations`,
      reservationPayload,
      {
        headers,
        tags: { endpoint: 'create-reservation' },
      }
    );

    const reservationCheck = check(reservationResponse, {
      'reservation response received': (r) => r.status > 0,
      'reservation status acceptable': (r) => [202, 409, 422, 403].includes(r.status),
    });

    requestCounter.add(1);
    if (reservationResponse.timings.duration > 2000) slowRequests.add(1);
    errorRate.add(!reservationCheck);
    responseTime.add(reservationResponse.timings.duration);
    sleep(Math.random() * 0.1 + 0.05);

    // If reservation was created, test status check
    let sessionId = null;
    if (reservationResponse.status === 202) {
      try {
        const data = JSON.parse(reservationResponse.body);
        sessionId = data.session_id;
      } catch (e) {
        // Continue if parsing fails
      }
    }

    // 7. GET /api/v1/reservation-status (if we have session_id)
    if (sessionId) {
      const statusResponse = http.get(
        `${BASE_URL}/api/v1/reservation-status?session_id=${sessionId}`,
        {
          headers,
          tags: { endpoint: 'reservation-status' },
        }
      );

      const statusCheck = check(statusResponse, {
        'status check response received': (r) => r.status > 0,
      });

      requestCounter.add(1);
      if (statusResponse.timings.duration > 2000) slowRequests.add(1);
      errorRate.add(!statusCheck);
      responseTime.add(statusResponse.timings.duration);
      sleep(Math.random() * 0.1 + 0.05);

      // 8. POST /api/v1/resend-otp (occasionally, to test this endpoint)
      if (Math.random() > 0.8) { // 20% chance
        const resendOtpPayload = JSON.stringify({
          session_id: sessionId,
        });

        const resendOtpResponse = http.post(
          `${BASE_URL}/api/v1/resend-otp`,
          resendOtpPayload,
          {
            headers,
            tags: { endpoint: 'resend-otp' },
          }
        );

        const resendOtpCheck = check(resendOtpResponse, {
          'resend otp response received': (r) => r.status > 0,
        });

        requestCounter.add(1);
        if (resendOtpResponse.timings.duration > 2000) slowRequests.add(1);
        errorRate.add(!resendOtpCheck);
        responseTime.add(resendOtpResponse.timings.duration);
      }
    }
  }

  // Minimal sleep to maximize throughput
  sleep(Math.random() * 0.1 + 0.05);
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
  
  const summary = {
    timestamp: new Date().toISOString(),
    test_type: 'stress_test_all_endpoints',
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
    full_data: data,
  };
  
  return {
    [`results/stress-test-${timestamp}.json`]: JSON.stringify(summary, null, 2),
    'stdout': `
╔══════════════════════════════════════════════════════════════╗
║        STRESS TEST RESULTS - ALL API ENDPOINTS               ║
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

🎯 Tested Endpoints:
   ✅ GET  / (Home Page)
   ✅ GET  /api/v1/restaurant-settings
   ✅ GET  /api/v1/closed-dates
   ✅ GET  /api/v1/time-slots
   ✅ GET  /api/v1/availability
   ✅ GET  /api/v1/restaurant-settings?date=...
   ✅ POST /api/v1/reservations
   ✅ GET  /api/v1/reservation-status
   ✅ POST /api/v1/resend-otp

💡 System Status:
   ${failedRequests < 0.1 ? '✅ System handling load well' : failedRequests < 0.2 ? '⚠️  System under stress' : '❌ System overloaded'}
   ${throughput > 100 ? '🚀 Excellent throughput!' : throughput > 50 ? '✅ Good throughput' : '⚠️  Low throughput'}
   ${avgResponseTime < 1000 ? '✅ Response times acceptable' : avgResponseTime < 2000 ? '⚠️  Response times degrading' : '❌ Response times critical'}

📁 Full results saved to: results/stress-test-${timestamp}.json
`,
  };
}
