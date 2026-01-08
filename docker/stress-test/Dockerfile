# =============================================================================
# Stress Test Container
# Includes: wrk, Apache Bench (ab), hey, curl, jq
# =============================================================================
FROM ubuntu:22.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and stress testing tools
RUN apt-get update && apt-get install -y \
    # Basic utilities
    curl \
    wget \
    git \
    build-essential \
    unzip \
    # Apache Bench
    apache2-utils \
    # JSON processor
    jq \
    # Network tools
    netcat-openbsd \
    dnsutils \
    # Clean up
    && rm -rf /var/lib/apt/lists/*

# Install wrk (high-performance HTTP benchmarking tool)
RUN cd /tmp && \
    git clone https://github.com/wg/wrk.git && \
    cd wrk && \
    make && \
    cp wrk /usr/local/bin/ && \
    rm -rf /tmp/wrk

# Install hey (modern HTTP load testing tool)
RUN curl -L https://github.com/rakyll/hey/releases/download/v0.1.4/hey_linux_amd64 -o /usr/local/bin/hey && \
    chmod +x /usr/local/bin/hey

# Create working directory
WORKDIR /stress-tests

# Copy test scripts
COPY docker/stress-test/scripts/*.sh /stress-tests/
RUN chmod +x /stress-tests/*.sh

# Default command
CMD ["/bin/bash"]

