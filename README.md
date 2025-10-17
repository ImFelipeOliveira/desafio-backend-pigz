# Desafio Backend FIPE - Pigz

Este projeto é uma solução para o [Desafio Backend FIPE da Pigz](https://github.com/orangebr/desafio-backend-fipe-pigz), desenvolvido como um marketplace de veículos com comparação de preços baseada na Tabela FIPE.

## Sobre o Projeto

O desafio consiste em criar uma API REST para um marketplace de veículos que permite:

-   Cadastro e autenticação de usuários (vendedores e compradores)
-   Gerenciamento de anúncios de veículos
-   Integração com a Tabela FIPE para comparação de preços
-   Sistema de permissões granular (ACL)
-   Comparação de preços anunciados vs. FIPE

> **Nota sobre Testes**: Este projeto não inclui testes automatizados, pois se trata de um desafio técnico com propósito de demonstração de arquitetura e boas práticas de desenvolvimento. O foco está em apresentar a implementação de DDD e padrões de código e não em preparar uma aplicação para produção.

## Arquitetura

O projeto segue os princípios de **Domain-Driven Design (DDD)** e **Clean Architecture**, organizando o código em camadas bem definidas:

### Estrutura de Camadas

```
src/
├── Domain/              # Camada de Domínio (regras de negócio)
│   ├── Entity/          # Entidades de domínio (Vehicle, User, FipeEntry, etc.)
│   ├── ValueObject/     # Value Objects (Price, VIN, Year, etc.)
│   └── Repositories/    # Interfaces de repositórios
├── Application/         # Camada de Aplicação (casos de uso)
│   ├── UseCase/         # Use Cases (LoginUseCase, RegisterUseCase, etc.)
│   ├── DTO/             # Data Transfer Objects
│   └── Security/        # Interfaces de segurança (JwtManagerInterface)
└── Infra/              # Camada de Infraestrutura (implementações)
    ├── Persistence/
    │   └── Doctrine/    # Entidades Doctrine e Repositórios
    ├── Adapter/         # Adapters (JWT, APIs externas)
    └── Controller/      # Controllers HTTP
```

### Princípios Aplicados

1. **Inversão de Dependência**: Use Cases dependem de interfaces, não de implementações concretas
2. **Separação de Responsabilidades**: Cada camada tem responsabilidades bem definidas
3. **Domain-Driven Design**: Lógica de negócio concentrada no domínio
4. **SOLID**: Classes coesas, com responsabilidade única e extensíveis

## Stack

### Core

-   **PHP 8.2+**: Versão moderna com suporte a tipos união, enums, readonly properties
-   **Symfony 7.3**: Framework robusto para APIs REST
-   **Doctrine ORM 3.5**: Mapeamento objeto-relacional com suporte a entidades separadas

### Dependências Principais

#### Doctrine ORM (^3.5.2)

Definido como requisito pelo desafio técnico.

#### Firebase PHP-JWT (^6.11)

Biblioteca para geração e validação de tokens JWT. **Foi escolhida por questões de compatibilidade**: durante o desenvolvimento, identifiquei conflitos entre `lexik/jwt-authentication-bundle` e o Symfony 7.3, especialmente relacionados a dependências e requisitos de extensões PHP. A solução foi implementar um adapter customizado usando `firebase/php-jwt`, que oferece:

-   Total compatibilidade com Symfony 7.3 e PHP 8.2+
-   Suporte nativo a algoritmo RS256 (chaves assimétricas)
-   Controle total sobre claims e expiração de tokens
-   Biblioteca mantida e amplamente testada pela comunidade

#### Symfony Security Bundle

Gerenciamento de autenticação, autorização e ACL (Access Control Lists). Permite configurar roles hierárquicas, voters customizados para permissões granulares e integração com o sistema de JWT implementado.

### Dependências de Desenvolvimento

-   `symfony/maker-bundle`: Geração de código (controllers, entities, migrations)

### Infraestrutura (Docker)

-   **PHP 8.2 FPM Alpine**: Imagem leve e otimizada
-   **Nginx Alpine**: Servidor web
-   **MySQL Latest**: Banco de dados
-   **Redis Alpine**: Cache e sessões (futuro)

## Como Executar o Projeto

### Pré-requisitos

-   Docker & Docker Compose
-   Git

### 1. Clonar o Repositório

```bash
git clone https://github.com/ImFelipeOliveira/desafio-backend-pigz.git
cd desafio-backend-pigz
```

### 2. Chaves JWT (Já Incluídas)

As variáveis de ambiente e as chaves JWT já estão configuradas para desenvolvimento:

-   Variáveis: arquivo `.env.dev`
-   Chaves: diretório `config/jwt/`

**Nota**: As chaves JWT foram commitadas propositalmente apenas para facilitar a execução local após a clonagem do repositório.

### 3. Subir os Containers

O projeto possui scripts Composer para facilitar o desenvolvimento:

```bash
# Subir ambiente de desenvolvimento
docker compose -f docker-compose-dev.yaml up --build
```

A aplicação estará disponível em: `http://localhost:8080`

### 4. Executar Migrations

```bash
# Criar migration (após alterar entidades)
composer make-migration

# Aplicar migrations
composer migrate

# Ou manualmente:
docker compose -f docker-compose-dev.yaml exec app php bin/console doctrine:migrations:migrate
```

### 5. Parar o Ambiente

```bash
# Parar e remover volumes
composer stop

# Ou manualmente:
docker compose -f docker-compose-dev.yaml down -v
```

## Scripts Disponíveis

O `composer.json` inclui scripts úteis para desenvolvimento:

| Script           | Comando                   | Descrição                        |
| ---------------- | ------------------------- | -------------------------------- |
| `dev`            | `composer dev`            | Sobe o ambiente Docker           |
| `stop`           | `composer stop`           | Para containers e remove volumes |
| `make-migration` | `composer make-migration` | Gera nova migration              |
| `migrate`        | `composer migrate`        | Aplica migrations pendentes      |

## Autenticação & Autorização

### Roles Disponíveis

-   **ROLE_SELLER**: Vendedores (podem criar/editar veículos e dados FIPE)
-   **ROLE_BUYER**: Compradores (acesso somente leitura)

### Fluxo de Autenticação

1. **Registro**: `POST /api/auth/register` com `{ "email", "password", "role" }`
2. **Login**: `POST /api/auth/login` com `{ "email", "password" }`
3. **Token JWT**: Resposta contém token RS256 com claims de roles
4. **Requisições protegidas**: Header `Authorization: Bearer {token}`

### Sistema de Permissões

-   **Access Control Lists (ACL)**: Configurado via `security.yaml`
-   **Voters**: Lógica granular de autorização (ex.: editar apenas veículos próprios)
-   **Guards**: Validação de tokens JWT em cada requisição

## Banco de Dados

### Entidades Principais

-   **User**: Usuários do sistema (vendedores/compradores)
-   **Vehicle**: Veículos anunciados
-   **FipeEntry**: Dados da Tabela FIPE
-   **VehicleComparison**: Comparações de preço veículo vs FIPE

### Migrations

Migrations são versionadas e aplicadas automaticamente:

```bash
# Ver status das migrations
docker compose exec app php bin/console doctrine:migrations:status

# Executar migrations pendentes
composer migrate

# Reverter última migration (cuidado em produção!)
docker compose exec app php bin/console doctrine:migrations:migrate prev
```

## Estrutura de Pastas Completa

```
.
├── bin/                    # Scripts executáveis
├── config/                 # Configurações Symfony
│   ├── packages/           # Configurações de bundles
│   ├── routes/             # Definições de rotas
│   └── jwt/                # Chaves JWT (não commitadas)
├── docker/                 # Configurações Docker
│   ├── nginx/              # Configuração Nginx
│   └── php/                # Configuração PHP
├── public/                 # Entrada da aplicação
├── src/                    # Código-fonte (ver estrutura DDD acima)
├── var/                    # Cache e logs
├── vendor/                 # Dependências Composer
├── .env                    # Variáveis de ambiente (template)
├── .env.dev                # Variáveis de desenvolvimento
├── composer.json           # Definição de dependências
├── docker-compose-dev.yaml # Orquestração Docker
└── Dockerfile              # Imagem PHP customizada
```

## Desenvolvimento

### Adicionar Nova Feature

1. **Domain**: Criar/modificar entidades e value objects em `src/Domain`
2. **Application**: Criar UseCase em `src/Application/UseCase`
3. **Infrastructure**: Criar entidade Doctrine e repository em `src/Infra/Persistence`
4. **Controller**: Criar endpoint em `src/Infra/Controller`
5. **Migration**: Gerar migration com `composer make-migration`
6. **Aplicar**: Executar migration com `composer migrate` e validar endpoint

### Comandos Úteis

```bash
# Listar rotas
docker compose exec app php bin/console debug:router

# Ver serviços registrados
docker compose exec app php bin/console debug:container

# Validar autowiring
docker compose exec app php bin/console debug:autowiring

# Limpar cache
docker compose exec app php bin/console cache:clear
```
