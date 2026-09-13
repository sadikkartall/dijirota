FROM node:22-bookworm-slim
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --omit=dev --ignore-scripts
COPY lib ./lib
COPY scripts ./scripts
COPY admin ./admin
COPY assets ./assets
CMD ["node", "scripts/serve.mjs"]
