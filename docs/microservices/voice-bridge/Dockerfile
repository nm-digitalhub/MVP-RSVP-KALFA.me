# Voice Bridge Microservice - Dockerfile
# Multi-stage build for production-optimized image

# ============================================
# Stage 1: Dependencies
# ============================================
FROM node:20-alpine AS deps
WORKDIR /app

# Install dependencies based on package manager lock
COPY package.json package-lock.json* ./
RUN npm ci --only=production && \
    npm cache clean --force

# ============================================
# Stage 2: Build (if using TypeScript)
# ============================================
FROM node:20-alpine AS build
WORKDIR /app

COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Build step (if using TypeScript)
RUN if [ -f tsconfig.json ]; then \
        npm run build; \
    else \
        echo "No build step required (plain JS)"; \
    fi

# ============================================
# Stage 3: Production Image
# ============================================
FROM node:20-alpine AS production

# Install dumb-init for proper signal handling
RUN apk add --no-cache dumb-init

# Create non-root user
RUN addgroup -g 1001 -S voicebridge && \
    adduser -S -u 1001 -G voicebridge voicebridge

WORKDIR /app

# Copy built application and dependencies
COPY --from=build --chown=voicebridge:voicebridge /app/package*.json ./
COPY --from=build --chown=voicebridge:voicebridge /app/node_modules ./node_modules
COPY --from=build --chown=voicebridge:voicebridge /app/src ./src
COPY --from=build --chown=voicebridge:voicebridge /app/dist ./dist

# Set environment
ENV NODE_ENV=production \
    PORT=4000 \
    LOG_LEVEL=info

# Switch to non-root user
USER voicebridge

# Expose WebSocket port
EXPOSE 4000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD node --eval "require('http').get('http://localhost:4000/health', (r) => {process.exit(r.statusCode === 200 ? 0 : 1)})"

# Use dumb-init to handle signals properly
ENTRYPOINT ["dumb-init", "--"]

# Start the application
CMD ["node", "src/index.js"]
