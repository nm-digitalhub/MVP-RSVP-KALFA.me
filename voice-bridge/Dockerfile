# Voice Bridge Microservice - Multi-stage Dockerfile
# For production deployment to Kubernetes

# ============================================
# Stage 1: Dependencies
# ============================================
FROM node:20-alpine AS deps
WORKDIR /app

# Install dependencies
COPY package.json package-lock.json* ./
RUN npm ci --only=production && \
    npm cache clean --force

# ============================================
# Stage 2: Production Image
# ============================================
FROM node:20-alpine AS production

# Install dumb-init for proper signal handling
RUN apk add --no-cache dumb-init

# Create non-root user
RUN addgroup -g 1001 -S voicebridge && \
    adduser -S -u 1001 -G voicebridge voicebridge

WORKDIR /app

# Copy application files
COPY --from=deps --chown=voicebridge:voicebridge /app/package*.json ./
COPY --from=deps --chown=voicebridge:voicebridge /app/node_modules ./node_modules
COPY --chown=voicebridge:voicebridge src ./src

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
