# Практика AWS + Jenkins: ефемерна лабораторія деплою

## Контекст (навіщо це)

Мета — **практична навичка деплою**, а не працюючий продакшн. Проєкт не має
постійно висіти в AWS: режим роботи — «підняв → попрацював → погасив або знищив».

Це змінює не деталі, а сам характер вправи. Постійний хостинг налаштовують один
раз і забувають; ефемерна лабораторія піднімається десятки разів, і тому змушує
робити правильно те, що на постійному проді можна роками відкладати:
інфраструктуру як код, конфіг поза артефактом, відтворюваність з нуля.

Побічна вигода: вартість перестає бути фактором (див. «Контроль витрат») —
одиниці центів за сесію.

### Прийняті рішення

| Питання | Рішення | Чому саме так |
|---|---|---|
| Архітектура | EC2 + `docker-compose` | Найдешевше, найшвидший шлях до робочого результату; вчить основи (VPC, SG, SSH, Docker у проді) без відволікання на ECS/task definitions |
| Де Jenkins | Окрема EC2-інстанція | Змушує вирішити задачу «як CI достукається до прод-хоста» по-справжньому, а не сховати її під локальний `docker compose up` |
| База даних | MySQL у контейнері | Для лабораторії достатньо; RDS — окремим етапом пізніше |
| Обсяг | `warehouses` (Symfony) наскрізь, потім решта | Єдиний сервіс, що вже має `Dockerfile`; швидкий повний цикл, далі копіюємо патерн |

### Стартовий рівень (важливо для розподілу часу)

| Інструмент | Рівень | Наслідок для плану |
|---|---|---|
| Jenkins | **курс пройдено** | Етапи 4–5 стискаються; складність там не в Jenkins, а в AWS-специфіці (SSH з контейнера на інший хост, `known_hosts`, ключі) |
| Docker / docker-compose | у роботі | Етап 1 — це не «вчити Docker», а привести dev-образ до прод-вигляду |
| AWS | акаунт є, практики нема | Основне навантаження етапів 0 і 2 |
| **Terraform** | **не тримав у руках** | Етап 3 розгорнуто в покрокову вправу; це найдовший етап плану |

Через це етап 3 навмисно розписаний детальніше за решту — там ти вчиш новий
інструмент, а не застосовуєш знайомий.

### Головний принцип виконання

**Кожен крок спершу руками, і лише потім у Jenkins.**

Якщо почати з пайплайну, Jenkins буде чорною скринькою: коли білд впаде — а він
впаде — не буде зрозуміло, чи проблема в Jenkins, у Docker, чи в AWS. Спочатку ти
маєш уміти задеплоїти сервіс вручну за 10 хвилин; Jenkins тоді просто повторює
твої ж кроки, і кожен його етап тобі знайомий.

---

## Стан «до» (що доведеться переробити)

Поточний Docker-сетап `warehouses` — суто для розробки. Це нормально, він таким і
мав бути, але в прод не поїде як є:

- **`warehouses/Dockerfile`** робить `COPY . /var/www/html` **без** `composer
  install`. Локально це працює лише тому, що `docker-compose.yml` підмонтовує
  `.:/var/www/html` поверх образу — тобто реально використовується локальний
  `vendor/`. На EC2 бінд-маунта не буде, і образ приїде без залежностей.
- **Немає `.dockerignore`** → в образ потрапить `vendor/`, `var/`, `node_modules/`,
  `.git/`, `.idea/`, а також `.env` з секретами.
- **`docker-compose.yml`** підміняє `CMD` на `php -S 0.0.0.0:80` — вбудований
  PHP-сервер, який офіційно задокументований як непридатний для продакшену
  (однопотоковий, без обробки статики, без стійкості до збоїв).
- **`warehouses/nginx/default.conf`** написаний під `fastcgi_pass web:9000`, але
  сервісу `nginx` у `docker-compose.yml` **немає взагалі** — конфіг висить сиротою
  й ніколи не використовується. Плюс ім'я апстріму `web` доведеться змінити.
- **`warehouses/crontab` порожній**, а `Dockerfile` його встановлює
  (`crontab /etc/cron.d/cron-schedule`) і запускає `cron` у `CMD`. Порожній crontab
  безпечний, але це зайва рухома частина в прод-образі.
- **`CORS_ALLOW_ORIGIN`** у `.env.example` захардкоджений регуляркою на
  `localhost|127.0.0.1`. Після деплою фронт з іншого origin отримає CORS-помилку.
- **`.env` у `.gitignore`** (рядок `.env` у секції framework-bundle) — тобто на
  сервері його нема звідки взяти, і секрети **обов'язково** інжектяться при
  деплої. Це добре, але означає, що крок «згенерувати `.env` на хості» —
  обов'язкова частина пайплайну, а не деталь.

### Що вже добре

- `composer.lock` є → збірка відтворювана.
- `warehouses/fixtures/` є → базу можна наповнювати на кожному підйомі.
- Міграції Doctrine є (`warehouses/migrations/`).
- API-префікс `/api` (`config/routes/api_platform.yaml`), і читання складу
  **публічне**: `security.yaml` → `{ path: ^/api/warehouse, roles: PUBLIC_ACCESS,
  methods: [GET] }`. Тобто для smoke-тесту не потрібен JWT — досить
  `GET /api/warehouse`.

---

## Цільова структура: новий репозиторій `shop-infra`

У проєкті три окремі git-репозиторії (`shop-2-back`, `warehouses`, `shop-front`),
і корінь `shop-wearhause/` **не** під git. Жоден з трьох не є правильним місцем
для прод-compose і Terraform — інфраструктура спільна для всіх трьох.

Створюємо четвертий репозиторій:

```
shop-infra/
├── terraform/
│   ├── main.tf              # VPC (default), SG, 2× EC2, outputs
│   ├── variables.tf         # region, instance_type, my_ip, key_name
│   ├── outputs.tf           # app_public_ip, jenkins_public_ip
│   └── user-data/
│       ├── app.sh           # docker + compose plugin + swap
│       └── jenkins.sh       # docker + запуск Jenkins-контейнера
├── warehouses/
│   ├── docker-compose.prod.yml
│   ├── nginx/default.conf   # виправлений: fastcgi_pass app:9000
│   └── .env.prod.tpl        # шаблон, значення підставляє Jenkins
├── scripts/
│   ├── up.sh                # terraform apply + вивід IP
│   ├── down.sh              # terraform destroy + пошук залишків
│   └── audit.sh             # що ще тарифікується в акаунті
└── README.md
```

---

## Етапи

| # | Етап | Що освоюєш | Орієнтовно |
|---|---|---|---|
| 0 | Гігієна акаунта | IAM, MFA, budget alerts | ~1 год |
| 1 | Прод-Dockerfile для `warehouses` | multi-stage build, nginx+fpm | ~2–3 год |
| 2 | Ручний деплой на EC2 (один раз) | VPC, SG, EC2, SSH, Docker у проді | ~2–3 год |
| 3 | **Terraform з нуля** | IaC, `apply`/`destroy` як ритм | **~5–6 год** ⚠️ новий інструмент |
| 4 | Jenkins на окремому EC2 | AWS-специфіка, не сам Jenkins | ~1–1.5 год |
| 5 | Jenkinsfile для `warehouses` | pipeline as code, smoke tests | ~1.5–2 год |
| 6 | Laravel + Angular за тим же патерном | специфіка кожного стека, рантайм-конфіг | ~4–5 год |
| 7 | ECR, RDS, HTTPS, JCasC | те, що відрізняє демо від проду | далі |

---

### Етап 0 — гігієна акаунта

Робиться **до** створення будь-якого ресурсу.

1. **AWS Budget з алертом на $5 і $20.**
   Billing → Budgets → Create budget → Cost budget → Monthly → email.
   Алерт коштує нуль і ловить не планові витрати, а забуті ресурси.
2. **MFA на root, окремий IAM-користувач для роботи.**
   Далі root не використовується взагалі.
3. **Окремий IAM-користувач `jenkins`** з мінімальними правами.
   На етапі 4–5 Jenkins не потребує AWS API взагалі (він ходить по SSH), тож
   ключі можна не створювати. Знадобляться на етапі 7 разом з ECR.
4. **EC2 key pair** (`shop-lab`), приватний ключ → `~/.ssh/shop-lab.pem`,
   `chmod 400`.
5. **`shop-infra` репозиторій** створено, порожній скелет закомічено.

**Критерій завершення:** бюджетний алерт створено, `aws sts get-caller-identity`
повертає IAM-користувача (не root).

---

### Етап 1 — прод-Dockerfile для `warehouses`

Робиться **повністю локально**, нічого не коштує. Без цього етап 2 не запуститься.

#### 1.1 `.dockerignore`

```
.git
.idea
vendor/
var/
node_modules/
public/build/
.env
.env.*
!.env.example
tests/
.phpunit.cache/
*.md
```

#### 1.2 `Dockerfile` — multi-stage

Чернетка, яку доведеться доганяти під час збірки (розширення PHP спливають
поштучно — це нормальна частина вправи):

```dockerfile
# --- Етап 1: залежності ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
      --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# --- Етап 2: рантайм ---
FROM php:8.3-fpm-alpine AS app
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql zip intl opcache \
 && apk del icu-dev libzip-dev oniguruma-dev
COPY --from=vendor /app /var/www/html
WORKDIR /var/www/html
RUN chown -R www-data:www-data var/
USER www-data
CMD ["php-fpm"]
```

Що змінилось проти поточного і чому:

- `composer install` **всередині** образу → образ самодостатній, бінд-маунт не
  потрібен.
- Спершу копіюються тільки `composer.json`/`composer.lock` → шар з `vendor/`
  кешується і не перезбирається при кожній зміні коду.
- `--no-dev` → у прод не їдуть phpunit, psalm, фікстур-бандл.
- `opcache` → без нього Symfony в проді працює в рази повільніше.
- `alpine` → образ ~150 МБ замість ~500 МБ; на `t3.micro` це відчутно.
- **Прибрано `cron`** — порожній `crontab` не виправдовує зайвий процес у
  контейнері. Коли з'явиться реальний розклад, це буде окремий сервіс у compose.

#### 1.3 nginx як окремий образ

Створити `warehouses/Dockerfile.nginx`:

```dockerfile
FROM warehouses-app:latest AS app
FROM nginx:alpine
COPY --from=app /var/www/html/public /var/www/html/public
COPY nginx/default.conf /etc/nginx/conf.d/default.conf
```

Статику nginx має віддавати сам, тому `public/` копіюється в його образ.

У `nginx/default.conf` виправити апстрім: `fastcgi_pass web:9000;` →
`fastcgi_pass app:9000;` (двічі, у обох `location`).

#### 1.4 `docker-compose.prod.yml`

```yaml
services:
  app:
    image: warehouses-app:${TAG:-latest}
    env_file: .env.prod
    depends_on:
      db: { condition: service_healthy }
    restart: unless-stopped

  nginx:
    image: warehouses-nginx:${TAG:-latest}
    ports: ["80:80"]
    depends_on: [app]
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes: [db_data:/var/lib/mysql]
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      retries: 5
    restart: unless-stopped

volumes:
  db_data:
```

Відмінності від dev-версії: немає бінд-маунтів, немає phpmyadmin, немає
проброшеного назовні порту 3306 (база доступна лише всередині мережі compose),
є healthcheck і `restart: unless-stopped`.

**Критерій завершення етапу:** локально
`docker compose -f docker-compose.prod.yml up -d`, потім
`curl -fsS http://localhost/api/warehouse` повертає JSON. Жодного бінд-маунта.

---

### Етап 2 — ручний деплой на EC2, один раз

Мета етапу — **не** «щоб працювало», а «зрозуміти, з яких ресурсів це
складається». Тут ти власноруч наступиш на всі граблі, які потім Jenkins
відтворював би мовчки: security group без відкритого порту, `docker: permission
denied` через групу `docker`, контейнер, що падає бо `APP_SECRET` порожній,
міграції, які треба запустити окремо. Кожна з них — 5 хвилин у SSH-сесії і
година, коли прилітає як червоний білд.

Кроки (через консоль AWS, свідомо руками):

1. **Security group** `shop-lab-app`: inbound `22` тільки з твого IP, `80` з
   `0.0.0.0/0`. Порт 3306 назовні **не** відкривати.
2. **EC2**: Amazon Linux 2023, `t3.small`, key pair `shop-lab`, диск 16 ГБ gp3.
   Про вибір типу — див. нижче.
3. **SSH**: `ssh -i ~/.ssh/shop-lab.pem ec2-user@<IP>`.
4. **Docker + compose plugin** (на AL2023 плагін compose не ставиться з коробки —
   це реальна пастка):
   ```bash
   sudo dnf install -y docker git
   sudo systemctl enable --now docker
   sudo usermod -aG docker ec2-user   # потрібен re-login!
   sudo mkdir -p /usr/local/lib/docker/cli-plugins
   sudo curl -SL https://github.com/docker/compose/releases/latest/download/docker-compose-linux-x86_64 \
     -o /usr/local/lib/docker/cli-plugins/docker-compose
   sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
   ```
5. **Swap** (обов'язково на 1–2 ГБ RAM, інакше `composer install` вб'є OOM killer):
   ```bash
   sudo dd if=/dev/zero of=/swapfile bs=1M count=2048
   sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
   ```
6. **Код**: `git clone` репозиторію `warehouses` (deploy key) + `shop-infra`.
7. **`.env.prod`** створити руками: `APP_ENV=prod`, `APP_DEBUG=0`,
   `APP_SECRET=$(openssl rand -hex 16)`, `DATABASE_URL=mysql://...@db:3306/...`,
   `JWT_SHARED_SECRET=...`, `CORS_ALLOW_ORIGIN=^https?://<IP-фронта>(:[0-9]+)?$`.
8. **Підйом**: `docker compose -f docker-compose.prod.yml up -d --build`.
9. **Міграції**: `docker compose exec app php bin/console doctrine:migrations:migrate -n`.
10. **Фікстури**: `docker compose exec app php bin/console doctrine:fixtures:load -n`.
11. **Перевірка**: `curl -fsS http://<IP>/api/warehouse`.
12. **`terminate` інстанції.** Свідомо. Наступного разу це робитиме Terraform.

#### Про тип інстанції

`t3.micro` (1 ГБ) вміщується у класичний free tier (750 год/міс перші 12 місяців,
якщо акаунт під нього підпадає), але Symfony 7.2 + Doctrine + MySQL поруч — це
впритул, і `composer install` регулярно ловить OOM навіть зі swap.

`t3.small` (2 ГБ) коштує приблизно на 1 цент за годину більше. В ефемерному режимі
це **~4 центи різниці за сесію** — не варте зекономлених нервів. Рекомендація:
`t3.small`, swap все одно налаштувати.

**Критерій завершення:** `curl` з твоєї машини повертає JSON з EC2; інстанцію
знищено; ти можеш перелічити з пам'яті, які саме ресурси створив.

---

### Етап 3 — Terraform з нуля ⚠️ новий інструмент

Ключова зміна порівняно з «постійним» деплоєм: **IaC переїжджає з розряду
«правильна практика колись» у розряд необхідності**. Коли інфра піднімається й
зноситься десятки разів, клікати в консолі стає нестерпно вже на третій раз.

Ефемерність тут працює на тебе: ти відчуєш цінність Terraform фізично, а не
приймеш на віру.

**Terraform тут — новий інструмент, тому етап розписаний покроково.** Хороша
новина: Terraform значно менший за Jenkins. Уся модель — чотири команди й шість
понять. Те, що зазвичай приймають за складність Terraform, майже завжди
виявляється незнанням AWS, а його ти вже пройдеш на етапі 2. Тому порядок
«спершу руками, потім Terraform» тут критичний: ти не вчиш дві незнайомі речі
одночасно. На етапі 2 незнайома тільки AWS-консоль, на етапі 3 — тільки синтаксис,
а **що** описувати ти вже знаєш.

#### Мінімум теорії: чотири команди

```bash
terraform init       # завантажити провайдер AWS у ./.terraform
terraform plan       # показати, що зміниться — НІЧОГО не робить
terraform apply      # привести реальність у відповідність до конфігу
terraform destroy    # знести все, що описано в конфігу
```

Головна ідея: ти описуєш **бажаний стан**, Terraform сам рахує різницю з
реальністю. Логіки «якщо існує — онови, інакше створи» не пишеш ніколи.

#### Мінімум теорії: шість понять

| Поняття | Що це | Приклад |
|---|---|---|
| `provider` | З якою хмарою говоримо | `provider "aws" { region = "eu-central-1" }` |
| `resource` | Те, що Terraform **створює** | `resource "aws_instance" "app" {}` |
| `data` | Те, що Terraform **читає** (існує без нього) | `data "aws_ami" "al2023" {}` |
| `variable` | Вхідний параметр | `var.my_ip` |
| `output` | Що показати після `apply` | публічний IP інстанції |
| **`state`** | Мапа «конфіг ↔ реальні ресурси» | файл `terraform.tfstate` |

Перші п'ять — синтаксис, вивчаються за годину. Шосте — єдина концептуально нова
річ, і саме на ньому спотикаються; про нього окремо у кроці 3.3.

#### Покрокова вправа

Формат як у `warehouses-reservation-domain-layer.md`: не «прочитай і зроби», а
маленькі кроки з негайним фідбеком. Кожен крок — окремий `apply`, щоб бачити
результат одразу, а не дебажити 100 рядків.

**3.1 — Підготовка (~20 хв)**

```bash
# Terraform CLI
wget -O- https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" \
  | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt update && sudo apt install terraform
terraform version

# AWS CLI + креденшели IAM-користувача з етапу 0
aws configure
aws sts get-caller-identity          # має показати IAM-юзера, не root
```

Одразу в `shop-infra/.gitignore`:

```
*.tfstate
*.tfstate.*
.terraform/
.terraform.lock.hcl   # (цей можна й комітити, але для соло-лабораторії не обов'язково)
*.tfvars              # тут лежатиме твій IP та інші локальні значення
```

**3.2 — Найменший можливий конфіг (~30 хв)**

Мета кроку — **відчути цикл**, а не написати правильно. Один файл `main.tf`,
одна інстанція, захардкоджено все:

```hcl
provider "aws" {
  region = "eu-central-1"
}

resource "aws_instance" "app" {
  ami           = "ami-XXXXXXXX"   # підглянь у консолі, тимчасово хардкод
  instance_type = "t3.small"
  tags          = { Name = "shop-lab-app" }
}
```

```bash
terraform init
terraform plan     # прочитай вивід уважно: "1 to add, 0 to change, 0 to destroy"
terraform apply
# перевір у консолі AWS, що інстанція справді з'явилась
terraform destroy
```

Зроби цей цикл **двічі**. Мета — щоб `plan → apply → destroy` перестав викликати
напругу.

**3.3 — Розібратись зі станом (~20 хв, найважливіший крок)**

Після `apply` відкрий `terraform.tfstate` і подивись, що там. Це JSON, у якому
записано: «ресурс `aws_instance.app` — це реальний `i-0abc123...`».

Що з цього випливає:

- Terraform знає про твої ресурси **тільки** звідси. AWS він не сканує.
- **Втратив файл — Terraform забув про ресурси.** Вони живі й далі
  тарифікуються, але `destroy` їх уже не знесе. Для ефемерної лабораторії це
  найгірший сценарій — саме тому в `down.sh` є перевірка залишків через
  `aws ec2 describe-*`.
- **У стані лежать відкритим текстом усі значення**, включно з тим, що ти вважав
  секретами. Тому `*.tfstate` у `.gitignore` — не побажання.
- Локальний стан для соло-лабораторії — нормально. S3-бекенд з блокуванням
  потрібен, коли з інфраструктурою працює більше однієї людини.

Експеримент, який варто зробити руками: після `apply` зайди в консоль AWS і зміни
тег інстанції. Потім `terraform plan`. Побачиш, що Terraform хоче повернути як
було — це **drift**. Правило з цього: після переходу на Terraform у консоль ходиш
дивитись, не редагувати.

**3.4 — Прибрати хардкод AMI через `data` (~20 хв)**

Хардкод AMI ламається при зміні регіону і застаріває. Замінюємо на запит:

```hcl
data "aws_ami" "al2023" {
  most_recent = true
  owners      = ["amazon"]
  filter {
    name   = "name"
    values = ["al2023-ami-*-x86_64"]
  }
}

resource "aws_instance" "app" {
  ami = data.aws_ami.al2023.id     # ← замість хардкоду
  ...
}
```

Ключова різниця: `resource` Terraform створює і знищує, `data` — лише читає.
`destroy` не чіпає AMI Amazon'а.

**3.5 — Змінні (~20 хв)**

Виносимо все, що змінюється, у `variables.tf`:

```hcl
variable "region"        { default = "eu-central-1" }
variable "instance_type" { default = "t3.small" }
variable "key_name"      { default = "shop-lab" }
variable "my_ip"         { description = "Твій публічний IP без маски" }
```

Значення без `default` Terraform запитає інтерактивно або візьме з
`terraform.tfvars` (він у `.gitignore` — там твій IP).

Свій поточний IP: `curl -s ifconfig.me`.

**3.6 — Security group і неявні залежності (~40 хв)**

Тут з'являється головна «магія» Terraform:

```hcl
data "aws_vpc" "default" { default = true }

resource "aws_security_group" "app" {
  name   = "shop-lab-app"
  vpc_id = data.aws_vpc.default.id

  ingress {                                     # SSH — тільки я
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["${var.my_ip}/32"]
  }

  ingress {                                     # HTTP — усі
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {                                      # назовні — все
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_instance" "app" {
  ami                    = data.aws_ami.al2023.id
  instance_type          = var.instance_type
  key_name               = var.key_name
  vpc_security_group_ids = [aws_security_group.app.id]   # ← ось воно
}
```

Ти **не** писав, що групу треба створити раніше за інстанцію. Terraform побачив
посилання `aws_security_group.app.id` всередині `aws_instance` і побудував граф
залежностей сам. При `destroy` — той самий граф у зворотному порядку.

Це і є та ідея, заради якої Terraform існує: залежності виводяться з посилань, а
не описуються вручну.

**3.7 — `user_data` і `output` (~30 хв)**

```hcl
resource "aws_instance" "app" {
  # ...
  user_data = file("user-data/app.sh")

  root_block_device {
    volume_size = 16
    volume_type = "gp3"
  }
}

output "app_public_ip" { value = aws_instance.app.public_ip }
```

`user-data/app.sh` — це скрипт з кроків 4–5 етапу 2 (docker, compose plugin,
swap, група `docker`). Після цього інстанція готова приймати деплой одразу після
`apply`, без жодного ручного SSH.

⚠️ `user_data` виконується **тільки при першому запуску** інстанції. Змінив
скрипт — потрібен `terraform destroy && apply` (або `terraform taint`), простий
`apply` його не перезапустить. Це класична пастка: правиш скрипт, а нічого не
змінюється.

**3.8 — Друга інстанція для Jenkins (~20 хв)**

Копія попереднього блоку з іншою security group: порти `22` і `8080` — **тільки з
твого IP**. Плюс `output "jenkins_public_ip"`.

**3.9 — П'ять циклів (~40 хв)**

Не пропускати. `terraform apply` → перевірити → `terraform destroy` п'ять разів
поспіль. Мета — щоб цикл став рефлексом, бо саме на ньому тримається весь
ефемерний режим. До п'ятого разу ти вже не думатимеш про Terraform і зможеш
думати про те, що деплоїш.

#### Що описуємо

```hcl
# Використовуємо default VPC — свою створювати не треба
data "aws_vpc" "default" { default = true }
data "aws_subnets" "default" { ... }

resource "aws_security_group" "app"     { ... }  # 22 (мій IP), 80 (всі)
resource "aws_security_group" "jenkins" { ... }  # 22 і 8080 — ТІЛЬКИ мій IP
resource "aws_instance" "app"     { user_data = file("user-data/app.sh") }
resource "aws_instance" "jenkins" { user_data = file("user-data/jenkins.sh") }

output "app_public_ip"     { value = aws_instance.app.public_ip }
output "jenkins_public_ip" { value = aws_instance.jenkins.public_ip }
```

#### ⚠️ Два правила, порушення яких коштує грошей

1. **Ніякого NAT Gateway.** Він коштує ~$32/міс і **не вимикається ніколи** — це
   найпоширеніша причина несподіваного рахунку. Популярні модулі
   (`terraform-aws-modules/vpc`) створюють його за замовчуванням. Тому: беремо
   **default VPC** з публічною підмережею, або пишемо свою VPC вручну з
   `enable_nat_gateway = false`. Інстанціям у публічній підмережі NAT не потрібен.
2. **Ніяких Elastic IP.** З лютого 2024 AWS тарифікує всі публічні IPv4-адреси, а
   EIP, що простоює, — так само. Автоматично призначений публічний IP звільняється
   при зупинці інстанції; EIP — ні. Для лабораторії EIP не потрібен, зміну IP
   вирішуємо інакше (див. «Пастка 1»).

#### Типові помилки новачка (щоб упізнати, а не дебажити годину)

| Симптом | Причина |
|---|---|
| Правиш `user_data`, `apply` — нічого не змінилось | `user_data` виконується лише при першому запуску; треба `destroy && apply` або `terraform taint` |
| `plan` хоче знести й створити заново | Змінив поле, яке не можна оновити «на місці» (`ami`, `user_data`) — читай `# forces replacement` у виводі |
| `destroy` не бачить ресурсів | Втрачений або не той `terraform.tfstate` — ресурси живі й тарифікуються, шукай через `aws ec2 describe-*` |
| `Error: InvalidGroup.NotFound` | Ресурс видалили руками в консолі; `terraform refresh` або `destroy` + `apply` |
| SSH не пускає після `apply` | `my_ip` застарів (динамічний IP провайдера) — перевір `curl -s ifconfig.me` |
| Довго висить `Still creating...` | Нормально: EC2 стартує 30–60 с, і це не помилка |
| `plan` хоче відкотити твої зміни в консолі | Drift. Так і має бути — у консоль ходиш дивитись, не редагувати |

**Критерій завершення етапу:**

1. `terraform apply` → через ~3 хвилини обидві інстанції готові, `output` показує IP.
2. SSH на app-інстанцію працює, `docker ps` виконується без `sudo`, swap увімкнено.
3. `terraform destroy` → `terraform show` порожній.
4. Цикл `apply`/`destroy` повторено **п'ять разів** без ручних правок.
5. Ти можеш пояснити своїми словами, що таке `terraform.tfstate` і чому його не
   можна втратити чи закомітити.

---

### Етап 4 — Jenkins на окремому EC2

> **Курс по Jenkins пройдено**, тож цей етап — не про Jenkins. Установка, плагіни,
> job-и, credentials тобі знайомі. Нове тут інше: як Jenkins **у контейнері на
> одній EC2** достукається до Docker **на іншій EC2**. Це задача AWS і SSH, а не
> Jenkins. Тому нижче — тільки те, що специфічне для цієї лабораторії; загальні
> речі пропускаємо.

#### Чому окрема інстанція

Дві інстанції, а не одна, змушують по-справжньому вирішити задачу «як CI
достукається до прод-хоста» (SSH-ключ у Jenkins credentials, `known_hosts`,
права), а не сховати її під локальний `docker compose up` на тому ж хості. Це і є
та частина, заради якої все затівається.

#### ⚠️ Jenkins не можна відкривати назовні

Security group для Jenkins: порти `22` і `8080` **тільки з твого IP**.
Публічний Jenkins знаходять сканери протягом годин, а Jenkins з правом
виконувати шелл-команди — це готовий майнер. Альтернатива, ще краща: не відкривати
8080 взагалі й ходити через SSH-тунель:
`ssh -i key.pem -L 8080:localhost:8080 ec2-user@<jenkins-ip>`.

#### Запуск

```bash
docker run -d --name jenkins --restart unless-stopped \
  -p 127.0.0.1:8080:8080 \
  -v jenkins_home:/var/jenkins_home \
  jenkins/jenkins:lts-jdk17
```

Плагіни: `Pipeline`, `Git`, `SSH Agent`, `Credentials Binding`, `Pipeline Utility Steps`.

#### Credentials (Manage Jenkins → Credentials)

| ID | Тип | Призначення |
|---|---|---|
| `app-host-ssh` | SSH private key | ключ `shop-lab.pem` для деплою на app-хост |
| `github-warehouses` | SSH key / PAT | клонування репозиторію |
| `jwt-shared-secret` | Secret text | **спільний** для Laravel і Symfony |
| `symfony-app-secret` | Secret text | `APP_SECRET` |
| `db-password` | Secret text | пароль MySQL |

#### ⚠️ `JWT_SHARED_SECRET` — окрема увага

Крос-сервісна авторизація тримається на тому, що Laravel (`shop-2-back`) підписує
HS256-токен, а Symfony (`warehouses`) його валідує **тим самим** ключем. Секрет
має бути байт-у-байт однаковим у обох `.env` — це сирий HMAC-ключ для обох
бібліотек.

У Jenkins це **один** credential, який інжектиться в обидва деплої. Якщо кожен
пайплайн генеруватиме свій — логін працюватиме, а запити до складу мовчки
даватимуть 401, і причину шукати неприємно.

#### ⚠️ Стан Jenkins і ефемерність

Джоби, credentials, плагіни, історія білдів живуть у томі `jenkins_home`.
Знищив інстанцію — налаштовуй усе заново (~40 хв).

**Тому: Jenkins-інстанцію між сесіями `stop`, не `destroy`.** Диск ~10 ГБ коштує
близько долара на місяць — це дешевше за 40 хвилин переналаштування щосесії.
Інстанція застосунку при цьому знищується вільно.

Перетворити Jenkins на одноразовий (JCasC) — етап 7, **після** того як пайплайни
вже працюють. Інакше кожна сесія починатиметься з дебагу самого Jenkins замість
роботи з Jenkins.

**Критерій завершення:** Jenkins відкривається через тунель, тестова job
`echo hello` проходить, `ssh` з Jenkins-контейнера на app-хост працює.

---

### Етап 5 — Jenkinsfile для `warehouses`

`Jenkinsfile` кладеться **в репозиторій `warehouses`** (pipeline as code), а не
налаштовується кліками.

```groovy
pipeline {
  agent any
  parameters {
    string(name: 'APP_HOST', defaultValue: '', description: 'Публічний IP app-інстанції')
  }
  environment {
    JWT = credentials('jwt-shared-secret')
    APP_SECRET = credentials('symfony-app-secret')
    DB_PASSWORD = credentials('db-password')
  }
  stages {
    stage('Checkout') { steps { checkout scm } }

    stage('Test') {
      steps {
        sh 'composer install --no-interaction --prefer-dist'
        sh 'vendor/bin/phpunit'
        sh 'vendor/bin/psalm --no-progress'
      }
    }

    stage('Deploy') {
      steps {
        sshagent(['app-host-ssh']) {
          sh '''
            ssh -o StrictHostKeyChecking=accept-new ec2-user@${APP_HOST} "
              cd ~/warehouses && git fetch --all && git reset --hard origin/main
              cat > .env.prod <<EOF
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=${APP_SECRET}
JWT_SHARED_SECRET=${JWT}
DATABASE_URL=mysql://symfony:${DB_PASSWORD}@db:3306/symfony
CORS_ALLOW_ORIGIN=^https?://${APP_HOST}(:[0-9]+)?\\$
EOF
              docker compose -f docker-compose.prod.yml up -d --build
              docker compose exec -T app php bin/console doctrine:migrations:migrate -n
            "
          '''
        }
      }
    }

    stage('Smoke test') {
      steps {
        sh 'curl -fsS http://${APP_HOST}/api/warehouse > /dev/null'
      }
    }
  }
  post {
    failure {
      echo 'Деплой невдалий — див. розділ про rollback'
    }
  }
}
```

Свідомі спрощення на цьому етапі, які виправляються далі:

- **Образ збирається на прод-хості** (`--build` через ssh). Це наївний підхід: під
  час збірки сервіс лежить, і `t3.small` ледве тягне. Саме тому на етапі 7 з'явиться
  ECR — і ти розумітимеш, яку конкретну проблему він вирішує.
- **`APP_HOST` — параметр job.** Бо IP змінюється (див. «Пастка 1»).
- **Тригер — ручний або `pollSCM`.** GitHub-вебхук не достукається до Jenkins,
  закритого на твій IP. Це нормально для лабораторії.

**Критерій завершення:** `Build with Parameters` → зелений білд → `curl` на
свіжому IP повертає JSON.

---

### Етап 6 — Laravel і Angular за тим же патерном

#### `shop-2-back` (Laravel)

Специфіка: `docker-compose.yml` зараз — це Laravel Sail (dev-інструмент з
Meilisearch, Mailpit, Selenium). Для прода потрібен власний compose: тільки
`app` (php-fpm) + `nginx` + `mysql` + `redis`. Sail не деплоять.

Плюс: `php artisan config:cache`, `route:cache`, `view:cache` у Dockerfile;
`APP_KEY` і **той самий** `JWT_SHARED_SECRET` з Jenkins credentials.

#### `shop-front` (Angular) — і головний урок етапу

`environment.apiUrl` / `environment.warehouseApiUrl` — це **compile-time
константи** з `src/environments/environments-local.ts`, які потрапляють у бандл на
етапі збірки. Використовуються більш ніж у 10 місцях: `auth.service.ts:26`,
`auth.interceptor.ts:17`, `warehouse.service.ts:19`, `admin-products.service.ts:11`
і далі.

Наслідок в ефемерному режимі: **при кожній зміні IP бандл треба перезбирати.**
На постійному хостингу це болить один раз; тут — кожну сесію.

Фікс, і він же цінність вправи: винести адреси в `public/assets/config.json`, який
підвантажується на старті застосунку через `provideAppInitializer`. Тоді один і
той самий білд працює будь-де, а конфіг підставляється при деплої одним `sed`.

Правило, яке з цього виноситься: **конфіг, що залежить від оточення, не
запікається в артефакт.** Те саме стосується `CORS_ALLOW_ORIGIN` у Symfony.

---

### Етап 7 — куди рости

За співвідношенням «користь / зусилля»:

1. **ECR** — Jenkins збирає образ у себе, пушить у ECR, app-хост робить `pull`.
   Прибирає збірку з прод-хоста (і OOM разом з нею), скорочує простій до секунд.
2. **HTTPS + домен** — Caddy як reverse proxy отримує сертифікат Let's Encrypt
   автоматично. Без домену не буде — потрібне DNS-ім'я, а не IP.
3. **Rollback** — тегувати образи `${GIT_COMMIT}`, зберігати попередній тег,
   при падінні smoke-тесту повертати `docker compose up` з попереднім `TAG`.
4. **RDS** — керована база, окрема підмережа, паролі в Secrets Manager.
   ⚠️ RDS не можна лишати запущеною між сесіями; `stop` для RDS автоматично
   скасовується через 7 днів.
5. **JCasC** — `jenkins.yaml` + job-dsl, щоб Jenkins піднімався з нуля однією
   командою. Ось тоді можна знищувати і його.

---

## Три пастки ефемерності

Найцінніша частина вправи — цього не навчишся на постійному хостингу.

### Пастка 1: IP змінюється при кожному старті

Автоматично призначений публічний IP звільняється при `stop` і видається новий
при `start`. Це ламає одразу чотири речі: SSH-ціль у Jenkins, `CORS_ALLOW_ORIGIN`
у Symfony, `apiUrl`/`warehouseApiUrl` у Angular, і URL вебхука.

**Рішення для лабораторії** (не EIP — він тарифікується):
- `APP_HOST` — параметр Jenkins job, береться з `terraform output`.
- Конфіг генерується при деплої, не лежить в образі.
- Angular читає адреси з `assets/config.json` у рантаймі.

### Пастка 2: стан Jenkins вмирає разом з інстанцією

Див. етап 4. Коротко: спершу `stop`, не `destroy`; JCasC — потім.

### Пастка 3: дані в MySQL вмирають разом з інстанцією

Для лабораторії це фіча, а не баг — але означає, що кожен підйом має сам себе
наповнити: міграції + фікстури обов'язковий крок пайплайну. `warehouses/fixtures/`
для цього вже є.

Побічна вигода: ти постійно перевіряєш, що міграції накатуються **з нуля** — те,
що на живому проді зазвичай виявляють надто пізно.

---

## Контроль витрат

### «Погасити» ≠ «видалити»

| Дія | Що припиняє тарифікуватись | Що **далі** тарифікується | Підйом |
|---|---|---|---|
| `stop` | Compute, публічний IPv4 | **EBS-диск** (~$0.08/ГБ-міс) | ~1 хв, стан збережено |
| `terminate` | Все | Нічого (якщо не лишив EIP/снапшотів) | ~5–10 хв, стан з нуля |

### Порядок величини

Цифри для `us-east-1`; **перевір під свій регіон** — ціни різняться, і це частина
вправи (Pricing Calculator + Cost Explorer).

- **Сесія 4 год, дві `t3.small`**: ~$0.05/год разом з IPv4 → **~20 центів за сесію**.
- **Між сесіями, якщо `stop`**: тільки диски, ~$1–2/міс.
- **Між сесіями, якщо `destroy`**: нуль.

Тобто вартість перестає бути фактором. Бюджетний алерт все одно потрібен — він
ловить не планові витрати, а забуті ресурси.

### Чеклист `down.sh` — що шукати після `terraform destroy`

Не просто `destroy`, а й перевірка залишків:

```bash
# інстанції, що ще живі
aws ec2 describe-instances \
  --filters "Name=instance-state-name,Values=running,stopped" \
  --query 'Reservations[].Instances[].[InstanceId,State.Name,InstanceType]' --output table

# диски без інстанції — найчастіша причина «я все вимкнув, а рахунок іде»
aws ec2 describe-volumes --filters "Name=status,Values=available" \
  --query 'Volumes[].[VolumeId,Size]' --output table

# Elastic IP — тарифікуються навіть коли простоюють
aws ec2 describe-addresses --query 'Addresses[].[PublicIp,InstanceId]' --output table

# снапшоти і AMI
aws ec2 describe-snapshots --owner-ids self --query 'Snapshots[].[SnapshotId,VolumeSize]' --output table

# NAT Gateway — якщо тут щось є, ти втрачаєш ~$32/міс
aws ec2 describe-nat-gateways --query 'NatGateways[].[NatGatewayId,State]' --output table
```

Раз на тиждень — Billing → Cost Explorer, звірити з очікуванням. Це теж навичка.

---

## Робочий ритм, до якого прийдемо

```bash
./scripts/up.sh          # terraform apply → віддає IP
# Jenkins → Build with Parameters → APP_HOST=<IP>
# ...практика...
./scripts/down.sh        # terraform destroy + пошук залишків
```

Jenkins-інстанція при цьому `stop`, а не `destroy` (до етапу 7).

---

## Перевірка (end-to-end)

Лабораторія вважається зібраною, коли з нуля, без ручних правок:

1. `./scripts/up.sh` піднімає app-інстанцію за ~3 хв.
2. `start` Jenkins-інстанції, тунель, Jenkins доступний.
3. `Build with Parameters` з новим `APP_HOST` → зелений білд.
4. `curl http://<APP_HOST>/api/warehouse` повертає JSON з фікстур.
5. Логін через Laravel видає JWT, з яким проходить `POST /api/warehouse/...`
   (перевірка, що спільний секрет однаковий).
6. `./scripts/down.sh` → усі перевірки залишків порожні.
7. Повторити пункти 1–6 наступного дня — має спрацювати без згадування, «що я там
   доналаштовував руками».

---

## Як це дає навичку

Пункт 7 у переліку вище — і є справжній критерій. Постійний хостинг налаштовують
один раз, і через місяць ніхто не пам'ятає, як він працює. Ефемерна лабораторія
змушує **відтворити все з нуля** щоразу, і саме повтор перетворює прочитане в
м'язову пам'ять: що таке security group і чому SSH не пускає, звідки береться
`vendor/` в образі, чому конфіг не можна запікати в артефакт, і що саме Jenkins
автоматизує — бо ти це вже робив руками.

Плюс набувається навичка, якої немає у більшості: **вміння знести інфраструктуру
і не залишити хвостів.** У реальних командах саме забуті ресурси, а не свідомі
рішення, роблять хмарний рахунок.

---

## Ресурси для вивчення Terraform

Порядок має значення: спершу офіційний туторіал (він рівно про наш випадок —
EC2 в AWS), відео — як доповнення, реєстр — як довідник під рукою.

### Обов'язковий мінімум

- **[Get Started: AWS](https://developer.hashicorp.com/terraform/tutorials/aws-get-started)** —
  офіційний туторіал HashiCorp. ~2 години, і він буквально піднімає EC2-інстанцію,
  тобто збігається з нашим етапом 3. Проходити руками, не читати.
  Ключові сторінки: [Install CLI](https://developer.hashicorp.com/terraform/tutorials/aws-get-started/install-cli) →
  [Create infrastructure](https://developer.hashicorp.com/terraform/tutorials/aws-get-started/aws-create) →
  [Manage infrastructure](https://developer.hashicorp.com/terraform/tutorials/aws-get-started/aws-manage).
  Розділ про HCP Terraform (хмарний бекенд) **можна пропустити** — для соло-лабораторії
  він не потрібен.
- **[Terraform Registry: AWS provider](https://registry.terraform.io/providers/hashicorp/aws/latest/docs)** —
  не туторіал, а довідник, у якому ти житимеш. Тут дивишся точні поля кожного
  ресурсу: [`aws_instance`](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/instance),
  [`aws_security_group`](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/security_group),
  [`aws_ami` (data)](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/ami).
  У кожної сторінки внизу є робочі приклади — звідти зручно копіювати.

### Відео

- **[Complete Terraform Course — From BEGINNER to PRO!](https://www.youtube.com/watch?v=7xngnjfIlK4)**
  (DevOps Directive) — найпопулярніший безкоштовний курс на YouTube, саме на AWS.
  Перші ~2 години покривають усе, що потрібно для етапу 3; далі йдуть модулі й
  remote state — це вже понад наші потреби, але подивитись варто, коли лабораторія
  запрацює.
- **[Terraform Tutorial Full Course for Beginners (2026)](https://www.youtube.com/watch?v=Bzccj0jjRBM)** —
  свіжіший варіант, якщо попередній здасться задовгим.

Українською безкоштовного відеокурсу саме по Terraform знайти не вдалось —
є лише платні DevOps-програми, де Terraform один з модулів
([Platma Academy](https://platma.academy/courses/kurs-devopsua/),
[добірка курсів](https://vchuvchu.com/courses-devops/)). Для нашого обсягу вони
надлишкові: офіційного туторіалу + довідника вистачить.

### Порада щодо порядку

Не намагайся пройти весь курс перед етапом 3. Ефективніше так:

1. Офіційний Get Started (~2 год) — до етапу 3.
2. Далі одразу крок 3.2 з плану, на **своїй** інфраструктурі.
3. Відео й реєстр — точково, коли впираєшся в конкретне питання.

Вивчене на власному проєкті тримається в пам'яті, вивчене на чужому прикладі —
ні. Тому мінімум теорії наперед, максимум — по ходу.

---

## Додаток — пов'язані наявні файли

**warehouses (Symfony 7.2)**
- `warehouses/Dockerfile` — переписати (етап 1.2)
- `warehouses/docker-compose.yml` — лишається dev-версією
- `warehouses/nginx/default.conf` — виправити `fastcgi_pass web:9000` → `app:9000`
- `warehouses/crontab` — порожній, прибрати з прод-образу
- `warehouses/.env.example` — `CORS_ALLOW_ORIGIN` захардкоджений на localhost
- `warehouses/.gitignore` — `.env` ігнорується → секрети інжектяться при деплої
- `warehouses/fixtures/` — наповнення бази на кожному підйомі
- `warehouses/config/packages/security.yaml` — `^/api/warehouse` GET публічний (smoke test)
- `warehouses/config/routes/api_platform.yaml` — префікс `/api`

**shop-2-back (Laravel 12)**
- `shop-2-back/docker-compose.yml` — Laravel Sail, для прода не годиться
- `shop-2-back/.env.example` — `JWT_SHARED_SECRET` має збігатися з Symfony

**shop-front (Angular 20)**
- `shop-front/src/environments/environments-local.ts` — compile-time `apiUrl` / `warehouseApiUrl`
- `shop-front/src/app/auth/services/auth.service.ts:26`
- `shop-front/src/app/auth/interceptors/auth.interceptor.ts:17`
- `shop-front/src/app/admin/shared/services/warehouse.service.ts:19`

**Пов'язані плани**
- `tasks/planned/warehouses-reservation-domain-layer.md` — доменний шар `warehouses`
