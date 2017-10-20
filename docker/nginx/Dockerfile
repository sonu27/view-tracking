FROM nginx:1.12-alpine
COPY default.conf /etc/nginx/conf.d/default.conf
COPY ssl/* /etc/ssl/private/
RUN chmod 700 /etc/ssl/private
