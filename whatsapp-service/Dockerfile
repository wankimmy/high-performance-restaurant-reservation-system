FROM node:20-alpine

WORKDIR /app

# Install git (required for some npm packages)
RUN apk add --no-cache git

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy application files
COPY . .

# Expose port
EXPOSE 3001

# Start service
CMD ["npm", "start"]
