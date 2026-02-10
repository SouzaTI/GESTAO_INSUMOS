# 📦 Sistema de Gestão de Insumos

[![Status do Projeto](https://img.shields.io/badge/status-operacional-blue.svg)](https://github.com/Estoquelogistica/gestao-de-insumos)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![Tecnologia](https://img.shields.io/badge/stack-XAMPP-orange.svg)]()
[![Banco de Dados](https://img.shields.io/badge/banco-MySQL-blue.svg)]()

---

## 📝 Descrição

**Contexto:** A gestão de materiais de uso e consumo apresentava gargalos operacionais devido a requisições informais e falta de um catálogo unificado. A ausência de um fluxo digital dificultava o controle de validade e a precisão do estoque físico em tempo real.

**Ação:** Foi desenvolvido o "Sistema de Gestão de Insumos", uma plataforma web centralizada para **controlar todo o ciclo de vida dos materiais da empresa**. O sistema integra um portal público de requisições a um painel administrativo robusto, permitindo o gerenciamento de entradas, saídas e cotações com total rastreabilidade.

**Resultado:** A solução implementou um processo padronizado e auditável. Com a unificação do catálogo e a automação das baixas de estoque, a gestão agora possui visibilidade total sobre o consumo por setor e alertas automáticos de estoque crítico. A segurança foi reforçada com logs de acesso e protocolos obrigatórios de troca de senha no primeiro login.

---

## 🔧 Funcionalidades Principais

✅ **Segurança e Auditoria:** Login com registro de IP e troca de senha obrigatória no primeiro acesso.
✅ **Dashboard de Performance:** KPIs de estoque físico, alertas de validade e consumo mensal.
✅ **Gestão de Catálogo:** CRUD completo com suporte a edição em lote de categorias.
✅ **Fluxo de Movimentação:** Registro detalhado de Compra Externa (+), Retirada Interna (-) e Cotação.
✅ **Requisição Digital Pública:** Portal externo para colaboradores com travas de integridade para nome e setor.
✅ **Acompanhamento em Tempo Real:** Monitor de pedidos para validar entregas de compras externas e internas.

---

## 📂 Estrutura do Projeto

```
gestao_insumos/
├── api/            # APIs para busca, estoque crítico e status
├── config/         # Configurações de banco de dados (db.php)
├── css/            # Estilos (Bootstrap 5 e Custom)
├── imagens/        # Recursos visuais e logos da empresa
├── img/            # Ícones e recursos gráficos herdados
├── js/             # Scripts Select2 e validações de formulário
├── lib/            # Bibliotecas auxiliares (PDF/Excel)
├── vendor/         # Dependências do Composer
├── login.php       # Tela de autenticação e logs de acesso
├── dashboard.php   # Painel principal com KPIs
├── produtos.php    # Gestão de catálogo e edição em lote
├── solicitar.php   # Portal público de requisições
├── trocar_senha.php # Módulo de segurança obrigatório
└── README.md       # Esta documentação
```

---

## 🛠️ Como Executar (Ambiente Local)

1. Instale o **XAMPP** (PHP 8.1+ e MySQL).
2. Copie a pasta do projeto para o diretório `htdocs/`.
3. Importe o banco de dados `gestao_insumos.sql` via **phpMyAdmin**.
4. Configure a conexão com o banco no arquivo `config/db.php`.
5. Acesse no navegador: `http://localhost/gestao_insumos/login.php`.

---

## 🔐 Usuários e Permissões

* **Autenticação**: Validação segura via `password_verify()`.
* **Primeiro Acesso**: Redirecionamento automático para troca de senha obrigatória.
* **Logs de Sistema**: Registro de ID do usuário e IP de origem para auditoria.

---

## 📸 Capturas de Tela

### 🔐 Segurança e Acesso
* **LOGIN:** Interface de autenticação segura para usuários cadastrados.
  <br>
  
  ![LOGIN](img/LOGIN.png)
* **PRIMEIRO ACESSO:** Fluxo obrigatório de redefinição de senha para novas contas.
  <br>

  ![PRIMEIRO_ACESSO](img/PRIMEIRO_ACESSO.png)

### 📊 Gestão Administrativa
* **ABA PAINEL:** Dashboard estratégico com indicadores de estoque físico e alertas.
  ![ABA_PAINEL](img/ABA_PAINEL.png)
* **ABA PRODUTOS:** Gerenciamento completo do catálogo de insumos de uso e consumo.
  ![ABA_PRODUTOS](img/ABA_PRODUTOS.png)
* **CADASTRAR INSUMO:** Módulo para inclusão técnica de novos materiais no inventário.
  ![CADASTRAR_INSUMO](img/CADASTRAR_INSUMOS.png)

### 🔄 Movimentações de Estoque
* **ABA MOVIMENTAÇÃO:** Central para registro e controle de fluxos de materiais.
  ![ABA_MOVIMENTAÇÃO](img/ABA_MOVIMENTAÇÃO.png)
* **COMPRA EXTERNA:** Registro de entrada de insumos para abastecimento do estoque.
  ![COMPRA_EXTERNA](img/COMPRA_EXTERNA.png)
* **RETIRADA INTERNA:** Processo de saída de materiais para consumo dos setores.
  ![RETIRADA_INTERNA](img/RETIRADA_INTERNA.png)
* **COTAÇÃO:** Ferramenta para levantamento de preços sem impacto no saldo físico.
  ![COTAÇÃO](img/COTAÇÃO.png)

### 📩 Requisições e Monitoramento
* **ABA REQUISIÇÕES EXTERNAS:** Gestão centralizada de pedidos recebidos via portal.
  ![ABA_REQUISIÇÕES_EXTERNAS](img/ABA_REQUISIÇÕES_EXTERNAS.png)
* **LINK PUBLICO:** Interface de solicitação digital (Solicitante, Setor e Itens).
  ![LINK_PUBLICO](img/LINK_PUBLICO.png)
* **ABA ACOMPANHAMENTO:** Monitoramento em tempo real do status de cada solicitação.
  ![ABA_ACOMPANHAMENTO](img/ABA_ACOMPANHAMENTO.png)

---


## 👨‍💻 Autor

**Matheus Cabral** Desenvolvimento de Sistemas.

---

## 📄 Licença

Projeto de uso interno corporativo.
