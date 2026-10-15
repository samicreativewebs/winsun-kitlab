FROM php:8.3-cli-alpine
WORKDIR /app
COPY . /app
RUN chmod +x /app/start.sh && mkdir -p /app/storage /app/public/uploads
EXPOSE 8080
CMD ["/app/start.sh"]
