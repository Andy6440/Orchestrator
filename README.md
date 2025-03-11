# 🏗️ Orchestrator (TaskFlow)

Orchestrator (TaskFlow) is a scalable, event-driven workflow automation system designed to optimize task management and improve efficiency in enterprise environments. Built with **Symfony 7**, **CQRS**, **Event Sourcing**, and **AI**, it enables **real-time task tracking**, **intelligent suggestions**, and **seamless collaboration**.

---

## ⚙️ **Current Features (Implemented)**
✅ **User Management** (Registration, Authentication, Roles)  
✅ **Event System** (Creating, Updating, Handling Events)  
✅ **Task Management** (CRUD Operations for Tasks)  

---

## 🚀 **Planned Features (Upcoming)**
❌ **AI-powered task suggestions and automation**  
❌ **Role-based access control (RBAC)**  
❌ **Real-time notifications (WebSockets, SSE, or Kafka)**  
❌ **Integration with external APIs**  
❌ **Frontend Dashboard (React/Vue.js)**  

---

## ⚙️ **Installation Guide**

### **1️⃣ Prerequisites**
Ensure you have the following installed:
- **PHP 8.2+**
- **Composer**
- **Symfony CLI** (optional but recommended)
- **MySQL 8.0+**
- **Node.js & npm** (if frontend is needed)

### **2️⃣ Clone the repository**
```sh
git clone https://github.com/yourusername/orchestrator.git
cd orchestrator
```

### **3️⃣ Install dependencies**
```sh
composer install
```

### **4️⃣ Set up environment variables**
Rename `.env.example` to `.env` and update the database credentials:
```sh
cp .env.example .env
nano .env
```
Example `.env` configuration:
```ini
DATABASE_URL="mysql://user:password@127.0.0.1:3306/database?serverVersion=8.0.33"
```
📌 The system requires two databases:
- `database` → Main database for production and development
- `database_test` → Database used exclusively for running tests

### **5️⃣ Set up the databases**
Create the required databases:
```sh
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS database;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS database_test;"
```
Ensure both databases are created by running:
```sh
mysql -u root -p -e "SHOW DATABASES;"
```
Now, run migrations for both databases:
```sh
php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
```
📌 Explanation of migrations:
- The first command runs migrations in the main database (`database`).
- The second command ensures migrations are applied to the test database (`database_test`).

If using fixtures for initial data, load them:
```sh
php bin/console doctrine:fixtures:load --no-interaction
APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction
```

### **6️⃣ Run the application**
```sh
symfony server:start
```
Or with PHP's built-in server:
```sh
php -S 127.0.0.1:8000 -t public
```

