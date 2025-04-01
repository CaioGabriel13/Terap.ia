# Projeto Chatbot Simples com PHP, MySQL, Bootstrap 5 e Font Awesome

Este projeto é um exemplo básico de sistema com cadastro, login e uma tela de chat (chatbot simples) utilizando:
- PHP com PDO
- MySQL para armazenamento de dados
- Bootstrap 5 para a interface
- Font Awesome para ícones
- Migrations SQL para criação das tabelas


## Requisitos

- **Servidor Web com PHP:** Pode ser o XAMPP, WAMP, LAMP ou similar.
- **MySQL:** Para criação do banco de dados.
- **MySQL Workbench (opcional):** Para executar as migrations manualmente, se desejar.
- **Navegador Web**

## Passo a Passo para Rodar o Projeto

1. **Clonar/baixar o projeto:**

   Copie todos os arquivos e pastas para o diretório do seu servidor web (por exemplo, a pasta `htdocs` do XAMPP).

2. **Configurar o Banco de Dados:**

   - Crie um banco de dados no MySQL (por exemplo, `nome_do_banco`).
   - Abra o arquivo `includes/config.php` e atualize os parâmetros:
     - `$db` com o nome do banco.
     - `$user` com o nome de usuário do MySQL.
     - `$pass` com a senha do MySQL.

3. **Executar as Migrations:**

   Existem duas maneiras:

   **a. Via MySQL Workbench:**
   - Abra o MySQL Workbench, conecte-se ao servidor e execute o conteúdo dos arquivos:
     - `migrations/001_create_users.sql`
     - `migrations/002_create_chat_tables.sql`

   **b. Via Script PHP Automático:**
   - Abra o terminal ou prompt de comando.
   - Navegue até o diretório do projeto.
   - Execute:
     ```
     php migrate.php
     ```
   - Ou, acesse via navegador: `http://localhost/terap.ia/migrate.php`.

4. **Rodar o Projeto:**

   - Acesse `cadastro.php` para criar um novo usuário:
     ```
     http://localhost/meu-projeto/cadastro.php
     ```
   - Após o cadastro, acesse `login.php` para efetuar o login:
     ```
     http://localhost/meu-projeto/login.php
     ```
   - Após o login, você será redirecionado para `chat.php` onde poderá enviar mensagens e ver a resposta do bot.
   - Use o botão **Deslogar** para encerrar a sessão.

## Observações

- **Segurança e Validações:**  
  Este projeto é um exemplo básico. Em ambientes de produção, adicione validações extras, proteção contra CSRF, sanitização de inputs e outras medidas de segurança.

- **Customização:**  
  Você pode personalizar os estilos no arquivo `assets/css/styles.css` e expandir as funcionalidades do chatbot conforme necessário.

- **Automatização das Migrations:**  
  O script `migrate.php` permite executar todas as migrations automaticamente, facilitando a instalação em novos ambientes.

---

Este README.md serve como guia para rodar e testar o projeto em diferentes ambientes. Qualquer dúvida ou problema, revise as configurações do banco de dados e certifique-se de que o servidor web esteja funcionando corretamente.
