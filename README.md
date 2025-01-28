# Psicomanager BackEnd Challenge

## Para executar o projeto

- Copie o ```.env.example``` para .env e altere o valor da variável DB_HOST para mysql (host/serviço do contêiner)

- Altere o valor da variável ```CACHE_DRIVER``` para ```redis``` no arquivo ```.env```

- Ainda no ```.env```, mantenha as configurações do REDIS desta forma

```
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CACHE_DB=0
REDIS_CLIENT=predis
```

- Suba os contêineres um de cada vez, na ordem mysql / php / nginx, ou então execute ```docker composer up``` seguidas vezes (utilizando ctrl+c em cada vez, por ex.)

- *Obs: mais detalhes sobre os problemas com o ambiente em docker no ```SOLUTION.md```

- Execute o Seeder

```docker compose exec -it php php artisan db:seed```

## Testes: utilize o script abaixo para que seja executado dentro do contêiner

- ```./run_tests.sh```
