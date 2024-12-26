# Psicomanager BackEnd Challenge

![Psicomanager](/logo_psicomanager.png)

## Case prático: Sistema de Gerenciamento de Tarefas

Desenvolver uma API RestFull simples para o gerenciamento de tarefas. A aplicação deve permitir:
1) Criar, listar, atualizar e excluir tarefas.
2) Filtrar tarefas por status (pendente, em progresso, concluída).
3) Registrar logs de eventos (como criação, atualização e exclusão de tarefas).

## Requisitos técnicos

- Utilize a linguagem PHP 8.1+
- Utilize o framework Laravel Lumen 10+ para desenvolver a API
- Use um banco MySql

## Stack

- TypeScript
- React
- React-Hook-Form
- Zod
- Styled-Components
- react-quill

## Protótipo

https://www.figma.com/design/kZ59orUONXXeNTCuQZmars/Desafio-T%C3%A9cnico---Dev-Front-End-(Pleno)?node-id=7-15782&node-type=canvas&t=UjEEhRjN4H9z4Heo-0

## História

### História do Usuário:

Como usuário do PsicoManager, eu quero ser capaz de ativar o PsicoBank, preencher as informações necessárias em uma interface de fácil navegação, e configurar minha conta bancária e opções de cobrança, para que eu possa começar a usar o PsicoBank de maneira eficiente, sem dificuldades.

Necessidade

O usuário precisa de um processo claro e guiado para:

1. Cadastrar uma conta bancária no sistema.
2. Configurar canais de envio e mensagens de cobrança de maneira personalizada.
3. Definir forma de pagamento e outras configurações relacionadas a cobranças.

Solução:

Desenvolver um fluxo de ativação com modal e passos sequenciais para guiar o usuário. O modal será interativo e terá validação em tempo real, além de ser responsivo. O design será fiel ao protótipo Figma para garantir a consistência visual e a melhor experiência de usuário.

---

Critérios de Aceite:

Geral

- Responsividade: O design e funcionalidades devem ser consistentes e funcionais tanto para desktop quanto para dispositivos móveis.

- Ser fiel ao protótipo realizar o desktop e mobile

- Sidebar fixa: Exibida com a seção Financeiro ativa e aberta por padrão. Conforme protótipo na ordem: painel, clientes, agenda, financeiro, relatórios, marketing, configuração e minha clinica, sendo clicavel para o case apenas o financeiro.

- Itens: Sidebar com ícones e títulos conforme a ordem do protótipo. Apenas o item Financeiro estará ativo para o caso de uso.
- Botão: Centralizado,>>financeiro com a opção Ativar PsicoBank que abrirá a modal.

Modal: Título do modal Ativar o PsicoBank - Etapas do modal: Cadastrar uma conta (passo 1), Canais de envio e Mensagem de cobrança (passo 2 ) e Forma de pagamento da cobrança (passo 3).

### Passo 1: Cadastrar uma Conta Bancária

1. Wizard (etapas/Steps):

- Passo atual check no step, etapas futuras desabilitadas até o usuário avançar.
- A interface do wizard deve ser interativa e exibir a etapa correta com destaque.

2. Campos obrigatórios:
   - Profissional: Pré-selecionado e desabilitado.
   - Banco: Dropdown obrigatória com opções do protótipo.
   - Tipo de Conta: Dropdown obrigatória com opções do protótipo.
   - Agência: Campo text obrigatório.
   - Conta com Dígito: Campo text obrigatório.
   - Tipo de Pessoa: Dropdown obrigatória.
   - CPF: Campo com máscara e obrigatório.
   - Telefone: Campo com máscara e obrigatório.
   - Nome Completo: Campo texto.
   - CEP: Campo com máscara e obrigatório.
   - Estado: Dropdown obrigatória.
   - Cidade: Campo texto obrigatório.
   - Endereço: Campo texto obrigatório.
   - Número: Campo texto obrigatório.

Após escolher "Pessoa Jurídica" no dropdown "Tipo de pessoa", os campos obrigatórios se adaptam para:

- Razão Social (campo de texto, obrigatório).
- CNPJ (campo com máscara, obrigatório, substituindo o CPF).
- Nome do responsável pela conta (campo de texto, obrigatório).
- CPF do responsável pela conta (campo com máscara, obrigatório).
- Todos os outros campos obrigatórios (Banco, Tipo de conta, Agência, Conta com dígito, Telefone, CEP, Estado, Cidade, Endereço e Número) permanecem os mesmos.
- Botões "Cancelar" e "Próximo" mantêm suas funcionalidades. O mesmo aos demais passos não sofrem alteração

3. Botões de Navegação:

   - Cancelar: Retorna ao estado anterior.
   - Próximo: Valida os campos obrigatórios e avança para a próxima etapa.

4. Validação em Tempo Real:
   - Se campos obrigatórios não forem preenchidos, deve exibir uma flag de erro e o comportamento do campo com erro

### Passo 2: Canais de Envio e Mensagens de Cobrança

1. Wizard:

   - A etapa atual não é marcada com um check , enquanto as etapas anteriores estarão com check e as posteriores desabilitadas.

2. Campos obrigatórios:

   - Profissional: Desabilitado e pré-preenchido do passo anterior.
   - Marcação Dinâmica: Campo de dropdown opções conforme prototipo. Ao clicar no botão inserir reflete na formatação e texto.
   - Conteúdo da Mensagem: Editor de texto com as opções de formatação: desfaz e refaz, Formato titulo 1 ao 6, negrito, itálico, alinhamento à direita, centralizado, à esquerda, justificar, lista desordenada, lista ordenada e link tooltip nas opções. O texto já esta previsto no protótipo assim como o comportamento ao inserir a marcação dinâmica no conteúdo da mensagem.

3. Botões de Navegação:
   - Cancelar: Fecha o modal.
   - Próximo: Avança para o próximo passo, se campos obrigatórios não forem preenchidos, deve exibir uma flag de erro e o comportamento do campo com erro.

### Passo 3: Forma de Pagamento da Cobrança

1. Wizard
   - A etapa atual será marcada com estado de acesso, enquanto as etapas anteriores estarão com check.
2. Campos obrigatórios:

   - Profissional: Desabilitado e pré-preenchido do passo anterior.
   - Métodos de Pagamento: Checkboxes com seleção única.
   - Cobrar Multa: Campo para selecionar o valor da multa, com campo de texto para inserção do valor em %.
   - Cobrar Juros: Campo para inserir juros, check.

3. Botões de Navegação:

   - Cancelar: Fecha o modal.
   - Concluir: Fecha o modal e exibe uma flag de sucesso após 3 segundos. Alterando a pagina final sidebar >>financeiro.

4. Validação em Tempo Real:
   - Se algum campo obrigatório não for preenchido, deve exibir uma flag de erro, assim como estado de campo.

### Pontos Adicionais

1. Persistência de Dados:

   - As informações preenchidas devem ser salvas ao avançar ou retroceder no wizard. Isso garante que o usuário não perca as informações ao navegar entre as etapas.

2. Comportamento de Componentes:

   - Implementar os **estados de hover**, **selected**, **disabled**, **error** e **rest** nos campos e botões.

3. Log de Dados:

   - Após o usuário clicar em **Concluir**, exibir as informações salvas no console do navegador para garantir que todos os dados estão sendo armazenados corretamente.

4. Design Fiel:

- Elementos visuais devem ser consistentes com o protótipo do Figma.

5. Flags

- Mensagem de sucesso deve ser exibida após a conclusão do cadastro e desaparecer automaticamente após 3 segundos

6. Campos:

- Campos com máscara devem formatar automaticamente os dados inseridos (ex: CPF, Telefone, CEP).
  -Comportamento dos componentes deve ser consistente em todos os estados: rest, selected, hover, error e disabled.
