# 🪪 Online Wallet Generator — WordPress Plugin

Plugin WordPress para geração de carteiras de identificação personalizadas com layout totalmente dinâmico, suporte a frente e verso, e opção de impressão.

---

## 📋 Sumário

- [Descrição](#descrição)
- [Funcionalidades](#funcionalidades)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Como usar](#como-usar)
- [Estrutura de arquivos](#estrutura-de-arquivos)
- [Histórico de versões](#histórico-de-versões)

---

## Descrição

O **Online Wallet Generator** permite criar carteiras de identificação digitais com layout 100% customizável via painel do WordPress. Você define a imagem de fundo, posiciona os campos de texto e foto arrastando e redimensionando, e o plugin gera a carteira pronta para impressão.

---

## Funcionalidades

- **Editor visual WYSIWYG** — arraste e redimensione campos diretamente sobre a imagem de fundo
- **Frente e verso** — configure layouts independentes para cada face da carteira (opcional)
- **Campos de texto** — nome, CPF, validade, cargo, etc. com fonte configurável
- **Campo de foto** — upload e posicionamento livre da foto do portador
- **Impressão fiel** — a impressão replica exatamente o que é visto no editor
- **Shortcode de consulta** — `[consultar_carteira]` para busca por CPF no frontend
- **Gerenciamento de carteiras** — listagem, adição e exclusão pelo painel admin

---

## Requisitos

- WordPress 5.8 ou superior
- PHP 7.4 ou superior
- Permissão `manage_options` para acessar o painel do plugin

---

## Instalação

1. Faça o download do arquivo `.zip` do plugin
2. No painel do WordPress, acesse **Plugins → Adicionar Novo → Enviar Plugin**
3. Selecione o arquivo `.zip` e clique em **Instalar Agora**
4. Após a instalação, clique em **Ativar Plugin**

Ou via FTP: extraia a pasta `online-wallet-generator` e envie para `wp-content/plugins/`.

---

## Como usar

### 1. Configurar o Layout

Acesse **Gerador Carteira → Configurar Layout** no menu do WordPress.

**Passo a passo:**

1. Clique em **"Selecionar Imagem de Fundo"** para carregar o template da carteira
2. Use **"+ Campo de Texto"** para adicionar campos como Nome, CPF, Validade, etc.
3. Use **"+ Campo de Imagem"** para adicionar um campo de foto do portador
4. Arraste e redimensione cada campo sobre a carteira para posicioná-lo
5. Passe o mouse sobre um campo para acessar os controles de rotação, tamanho de fonte e remoção
6. Marque **"Carteira tem frente e verso"** para configurar também o verso
7. Clique em **Salvar Configurações de Layout**

### 2. Cadastrar uma Carteira

Acesse **Gerador Carteira → Nova Carteira** e preencha os campos definidos no layout.

### 3. Imprimir

Na listagem (**Gerador Carteira**), clique em **Imprimir** ao lado da carteira desejada. Uma página de impressão será aberta com a carteira renderizada — clique no botão **🖨️ Imprimir Carteira**.

### 4. Consulta pelo Frontend

Insira o shortcode em qualquer página ou post:

```
[consultar_carteira]
```

O visitante poderá digitar o CPF para localizar e visualizar a própria carteira.

---

## Estrutura de arquivos

```
online-wallet-generator/
├── online-wallet-generator.php     # Arquivo principal do plugin
├── includes/
│   ├── class-owg-admin.php         # Painel administrativo (menus, settings, CRUD)
│   └── class-owg-wallet.php        # Endpoint de impressão e shortcode frontend
├── assets/
│   ├── css/
│   │   └── admin.css               # Estilos do editor visual
│   └── js/
│       └── admin.js                # Lógica de drag-and-drop, abas, upload
└── templates/
    └── print-wallet.php            # Template de impressão da carteira
```

---

## Histórico de versões

### v6.1 — Correções críticas
- Corrigido: campos de dados aparecendo por baixo da imagem de fundo na impressão (z-index)
- Corrigido: opção "frente e verso" não salvando corretamente (checkbox fora do form)
- Corrigido: canvas do editor com tamanho exato da imagem (remoção de border que deslocava posições)
- Background da impressão migrado de `background-image` CSS para tag `<img>` com `z-index: 0`

### v6.0 — Frente e verso + melhorias de precisão
- Adicionado suporte a frente e verso com abas independentes no editor
- Canvas do editor sem borda (`outline` substituiu `border`) para posicionamento pixel-perfect
- `background-size` definido em pixels absolutos para fidelidade entre editor e impressão
- Compatibilidade retroativa com configurações salvas em versões anteriores

### v5.3 — Base inicial
- Editor visual com drag-and-drop (jQuery UI)
- Suporte a campos de texto e imagem
- Geração e impressão de carteiras
- Shortcode `[consultar_carteira]`
