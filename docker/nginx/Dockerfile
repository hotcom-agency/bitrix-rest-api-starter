ARG NGINX_VERSION=1.25.4
ARG NGX_CACHE_PURGE_VERSION=2.5.3

# Stage 1: Build
FROM debian:bookworm AS builder
ARG NGINX_VERSION
ARG NGX_CACHE_PURGE_VERSION

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential wget ca-certificates zlib1g-dev libssl-dev libpcre3-dev && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /tmp/build

RUN wget -q http://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz && \
    tar -xzf nginx-${NGINX_VERSION}.tar.gz && \
    wget -q https://github.com/nginx-modules/ngx_cache_purge/archive/refs/tags/${NGX_CACHE_PURGE_VERSION}.tar.gz && \
    tar -xzf ${NGX_CACHE_PURGE_VERSION}.tar.gz

WORKDIR /tmp/build/nginx-${NGINX_VERSION}

RUN ./configure \
    --prefix=/etc/nginx \
    --sbin-path=/usr/sbin/nginx \
    --conf-path=/etc/nginx/nginx.conf \
    --error-log-path=/var/log/nginx/error.log \
    --http-log-path=/var/log/nginx/access.log \
    --pid-path=/var/run/nginx.pid \
    --lock-path=/var/run/nginx.lock \
    --user=nginx \
    --group=nginx \
    --with-http_ssl_module \
    --with-http_realip_module \
    --with-http_addition_module \
    --with-http_sub_module \
    --with-http_gzip_static_module \
    --with-http_stub_status_module \
    --with-http_auth_request_module \
    --with-http_v2_module \
    --with-stream \
    --with-stream_ssl_module \
    --with-compat \
    --add-module=/tmp/build/ngx_cache_purge-${NGX_CACHE_PURGE_VERSION} && \
    make && make install

# Stage 2: Runtime
FROM nginx:${NGINX_VERSION}
ARG NGINX_VERSION

RUN apt-get update && apt-get install -y libpcre3 && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/sbin/nginx /usr/sbin/nginx

RUN mkdir -p /var/cache/nginx/proxy && chown -R nginx:nginx /var/cache/nginx /var/log/nginx

CMD ["nginx", "-g", "daemon off;"]
