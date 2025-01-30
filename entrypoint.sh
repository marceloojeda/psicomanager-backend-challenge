#!/bin/bash

# Aguardar até que o Elasticsearch esteja acessível na porta 9200
while ! nc -z elasticsearch_container 9200; do
  echo "Esperando o Elasticsearch... aguardando 5 segundos."
  sleep 5
done

# Quando o Elasticsearch estiver pronto
echo "Elasticsearch está pronto!"

# Aguardar mais 5 segundos antes de iniciar o PHP-FPM (opcional)
sleep 5

# Iniciar o PHP-FPM
exec php-fpm
