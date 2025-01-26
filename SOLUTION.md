# Solutions

## Filtro em GET /users

- Filtros opcionais por id e nome vindos na "query string". Utilizando where "exato" para o id, where like para o nome

## Campo password sendo retornado em /users

- Adicionado campo 'password' no atributo ```$hidden``` do Model. Isto impede que o campo seja serializado automaticamente ao retornar dados ou em qualquer rotina que utilize o método ```getArray()```

## Recuperar dados de um único usuário

- Criado método/lógica no UserController referente à rota GET /users/{userId}

## POST /users não está criptografando as senhas

- Adicionada lógica para criptografar a senha antes de salvar no banco utilizando Make::hash por ser o padrão do Laravel

## Lentidão no endpoint GET /tasks?userId={id}

- Adicionada indexação no banco de dados para o campo 'user_id', que é a melhor forma de melhorar a performance em SELECTs com where, segundo a documentação do MySQL: https://dev.mysql.com/doc/refman/8.4/en/optimization-indexes.html. Outras técnicas podem e devem ser aplicadas em casos com volume de dados e de consultas, como paginação e cache.

## O endpoint POST /users permite criar usuários com emails duplicados

- Adicionada validação no endpoint utilizando regra "unique"

## O endpoint DELETE /users/{id} falha ao excluir usuários que possuem tarefas associadas

- Optei por excluir as tarefas órfãs, mas outra técnica interessante seria movê-las para um usuário admin, ou para outra tabela ```tasks_backup``` contendo o e-mail do usuário por exemplo, para que fosse possível restaurar posteriormente mas sem manter dados desnecessários na tabela principal. E uma possível melhoria seria desenvolver um recurso para demandar do administrador que redistribua as tarefas ou descarte-as permanentemente

## Logs importantes das operações

- Foram criados ```Observers``` para serem utilizados nos ```Models``` e disparar uma rotina e gravar os dados na tabela ```action_logs``` contendo o tipo de operação, a tabela, e os dados do ```Model```.

## Validação robusta nos endpoints

- Validações em POST /users para nome, email e senha com algumas restrições evitando nomes e senhas muito curtos(as)
- Validações em parâmetros GET numéricos, como "user_id" ou "id" utilizando o Validator ou regex na declaração da rota evitando acessos desnecessários ao banco de dados

## Testes

- Foram criados testes para praticamente todos os recursos, exceto para o teste de desempenho do endoint GET /tasks?userId{id}

## Docker / Ambiente dev

### Ajustes para execução do ambiente de desenvolvimento de forma independente do sistema operacional hospedeiro

- O comando ```composer install``` foi movido do Dockerfile para a diretriz "command" no ```docker-compose.yml```, para que seja executado após a inicialização do contêiner e assim o diretório "vendors" persista após a montagem do volume. Também foram removidas as rotinas de copiar a aplicação para o contêiner no Dockerfile, já que está sendo utilizado o volume isto não é necessário, considerando um ambiente dev.

- Erro ao executar composer install dentro do contêiner, por estar executando o git como root (ao baixar os pacotes via git clone)
- - Para resolver foi adicionado ao Dockerfile a criação de um usuário para executar os comandos no contêiner e ter permissão na aplicação

- Conflito de versão entre os pacotes travados no composer.lock e a versão do php do contêiner. O arquivo composer.lock provavelmente foi criado ao executar composer install fora do contêiner, pois está apontando para pacotes que requerem php8.2 enquanto o Dockerfile está com o php8.1
- - Para resolver existem duas opções, atualizar a imagem do conteiner para php8.2 ou remover o composer.lock e manter a compatibilidade com o php8.1. Vou considerar que é preciso manter a mesma versão do servidor, supostamente 8.1, e por isso vou excluir o composer.lock para reinstalar os pacotes mantendo a compatibilidade com o php8.1

- Contêiner php está "morrendo" na inicialização pois o servidor MySQL ainda não está totalmente pronto, apesar da diretriz "depends_on", que não considera outras operações que o servidor executa antes de ter o serviço disponibilizado, e o mesmo acontece com o NGINX por não encontrar o contêiner "php". Acredito que a solução adequada seria um shell script para aguardar o respectivo serviço estar disponível. Mas também vai funcionar, da forma que está, após seguidas tentativas de ```docker compose up```, ou se subir um contêiner de cada vez na ordem: mysql / php / nginx

- Correção do path na configuração do nginx ```default.conf```, na diretriz ```fastcgi_param SCRIPT_FILENAME```. O path deve ser correspondente à como o volume está montado no contêiner php, por isso foi adicionado "/public"
