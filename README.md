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

## API Endpoints

### Autenticação

#### POST `/api/auth/register`

Criar novo usuário

**Request:**

```json
{
    "email": "user@example.com",
    "password": "senha123",
    "confirmPassword": "senha123",
    "roles": ["ROLE_USER"] // Opcional, default: ROLE_USER
}
```

**Response (201):**

```json
{
    "id": "uuid",
    "email": "user@example.com",
    "roles": ["ROLE_USER"]
}
```

#### POST `/api/auth/login`

Autenticar usuário

**Request:**

```json
{
    "email": "admin@pigz.com",
    "password": "admin123"
}
```

**Response (200):**

```json
{
    "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

---

### Veículos (Vehicles)

#### POST `/api/vehicles`

Criar anúncio de veículo (Requer: `ROLE_ADMIN`)

**Headers:**

```
Authorization: Bearer {token}
```

**Request:**

```json
{
    "brand": "Toyota",
    "model": "Corolla",
    "version": "XEI 2.0",
    "category": "sedan",
    "year": 2020,
    "price": 85000.0,
    "currency": "BRL",
    "mileage": 45000,
    "fuelType": "flex",
    "transmission": "automatic",
    "fipeCode": "001004-1",
    "vin": "1HGBH41JXMN109186",
    "description": "Veículo em ótimo estado, único dono",
    "images": ["https://example.com/img1.jpg"]
}
```

**Response (201):**

```json
[
  {
    "id": "uuid",
    "brand": "Toyota",
    "model": "Corolla",
    ...
  }
]
```

#### GET `/api/vehicles`

Listar veículos com filtros e paginação

**Query Parameters:**

-   `page` (int, default: 1)
-   `limit` (int, default: 10, max: 100)
-   `brand` (string)
-   `model` (string)
-   `price_min` (float)
-   `price_max` (float)
-   `year_min` (int)
-   `year_max` (int)
-   `fuelType` (string)

**Example:**

```
GET /api/vehicles?page=1&limit=20&brand=Toyota&price_max=100000
```

**Response (200):**

```json
{
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 15
  }
}
```

#### GET `/api/vehicles/{id}`

Buscar veículo por ID

**Response (200):**

```json
[
  {
    "id": "uuid",
    "brand": "Toyota",
    "model": "Corolla",
    "price": 85000.00,
    ...
  }
]
```

#### PUT/PATCH `/api/vehicles/{id}`

Atualizar veículo (Requer: proprietário ou ROLE_ADMIN)

**Request:** mesmo formato do POST (campos que deseja atualizar)

#### DELETE `/api/vehicles/{id}`

Deletar veículo (soft delete) (Requer: proprietário ou ROLE_ADMIN)

**Response (200):**

```json
{
    "message": "Vehicle deleted successfully"
}
```

#### GET `/api/vehicles/{id}/compare`

Comparar veículo com tabela FIPE

**Response (200):**

```json
{
    "vehicle": {
        "id": "uuid",
        "brand": "Toyota",
        "model": "Corolla",
        "year": 2020,
        "price": 85000.0,
        "fipe_code": "001004-1"
    },
    "fipe": {
        "id": "uuid",
        "fipe_code": "001004-1",
        "price": 82000.0,
        "reference_month": "2025-10",
        "model_year": 2020
    },
    "comparison": {
        "vehicle_price": 85000.0,
        "fipe_price": 82000.0,
        "difference": 3000.0,
        "percentage_difference": 3.66,
        "status": "within_fipe",
        "recommendation": "Price aligned with FIPE table."
    }
}
```

---

### Tabela FIPE

#### GET `/api/fipe`

Listar entradas FIPE

**Query Parameters:**

-   `page`, `limit` (paginação)
-   `fipeCode` (string)
-   `brand` (string)
-   `model` (string)
-   `referenceMonth` (string, formato: YYYY-MM)

**Response (200):**

```json
{
  "data": [...],
  "pagination": {...}
}
```

#### POST `/api/fipe`

Registrar entrada FIPE manualmente (Requer: `ROLE_ADMIN`)

**Request:**

```json
{
    "fipeCode": "001004-1",
    "brand": "Toyota",
    "model": "Corolla XEI",
    "category": "sedan",
    "version": "2.0 16V",
    "fuelType": "flex",
    "price": 82000.0,
    "currency": "BRL",
    "referenceMonth": "2025-10",
    "modelYear": 2020
}
```

#### POST `/api/fipe/sync`

Sincronizar dados da API FIPE externa (Requer: `ROLE_ADMIN`)

**Request:**

```json
{
    "vehicleType": "carros",
    "brandCode": "21",
    "modelCode": "3",
    "yearCode": "2020-1"
}
```

**Response (201):**

```json
{
  "message": "FIPE data synchronized successfully",
  "fipe_entry": {...},
  "source_data": {...}
}
```

---

## Control de Acesso (ACL)

### Voters Implementados

#### VehicleVoter

-   `VEHICLE_CREATE`: Apenas `ROLE_ADMIN`
-   `VEHICLE_VIEW`: Todos autenticados
-   `VEHICLE_EDIT`: Proprietário ou `ROLE_ADMIN`
-   `VEHICLE_DELETE`: Proprietário ou `ROLE_ADMIN`

#### FipeVoter

-   `FIPE_CREATE`: Apenas `ROLE_ADMIN`
-   `FIPE_EDIT`: Apenas `ROLE_ADMIN`
-   `FIPE_DELETE`: Apenas `ROLE_ADMIN`
-   `FIPE_VIEW`: Todos autenticados
-   `FIPE_SYNC`: Apenas `ROLE_ADMIN`

### Exemplo de Uso

```php
// No controller
$this->denyAccessUnlessGranted('VEHICLE_EDIT', $vehicle);
```

---

## Comandos Úteis

### Criar Usuário Admin

```bash
docker compose -f docker-compose-dev.yaml exec app php bin/console app:seed:admin
# Credenciais default: admin@pigz.com / admin123

# Custom credentials:
docker compose -f docker-compose-dev.yaml exec app php bin/console app:seed:admin --email=admin@test.com --password=mypass
```

### Limpar Cache

```bash
docker compose -f docker-compose-dev.yaml exec app php bin/console cache:clear
```

### Ver Rotas Disponíveis

```bash
docker compose -f docker-compose-dev.yaml exec app php bin/console debug:router
```

### Instalar Dependências

```bash
docker compose -f docker-compose-dev.yaml exec app composer install
```

---

## Integração FIPE API

Este projeto utiliza a API pública da FIPE: https://deividfortuna.github.io/fipe/

### Adapter Pattern

Foi implementado um adapter (`FipeApiClient`) que abstrai a comunicação com a API externa, seguindo o princípio de inversão de dependência:

-   Interface: `Domain\Services\FipeServiceInterface`
-   Implementação: `Infra\Adapter\Fipe\FipeApiClient`

### Métodos Disponíveis

-   `getBrands(vehicleType)`: Lista marcas
-   `getModels(vehicleType, brandCode)`: Lista modelos
-   `getYears(vehicleType, brandCode, modelCode)`: Lista anos
-   `getVehicleValue(...)`: Busca preço FIPE completo

---

## Testes com Postman/Insomnia

### Fluxo Básico de Teste

1. **Criar Admin**

```bash
docker compose -f docker-compose-dev.yaml exec app php bin/console app:seed:admin
```

2. **Login** → `POST /api/auth/login` → Copiar token

3. **Criar Veículo** → `POST /api/vehicles` (com Bearer token)

4. **Sincronizar FIPE** → `POST /api/fipe/sync` (com Bearer token)

5. **Comparar Preços** → `GET /api/vehicles/{id}/compare`

6. **Listar com Filtros** → `GET /api/vehicles?brand=Toyota&price_max=100000`

---

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
