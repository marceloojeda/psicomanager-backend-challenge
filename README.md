# Psicomanager BackEnd Challenge

## Para executar o projeto

- copie o ```.env.example``` para .env e altere o valor da variável DB_HOST para mysql (host/serviço do contêiner)

- suba os contêineres um de cada vez, na ordem mysql / php / nginx, ou então execute ```docker composer up``` seguidas vezes (utilizando ctrl+c em cada vez, por ex.)

- mais detalhes sobre os problemas com o ambiente em docker no ```SOLUTION.md```

## Testes: utilize o script abaixo para que seja executado dentro do contêiner

- ```./run_tests.sh```
