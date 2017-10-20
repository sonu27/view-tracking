FROM nginx:1.12-alpine
COPY default.conf /etc/nginx/conf.d/default.conf
COPY selfsigned.crt /etc/ssl/private/
COPY selfsigned.key /etc/ssl/private/
RUN chmod 700 /etc/ssl/private
