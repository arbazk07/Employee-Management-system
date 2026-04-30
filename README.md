# 🧑‍💼 Employee Management System (EMS)

A full-stack **Employee Management System** built with **PHP**, **MySQL**, and a premium dark enterprise UI. Developed as a university database project for Section BAI-4A.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![XAMPP](https://img.shields.io/badge/Server-XAMPP-FB7A24?style=flat-square&logo=apache&logoColor=white)

---

## 📸 Features at a Glance

- 🔐 **Authentication** — Role-based login system (Admin / Employee)
- 📊 **Live Dashboard** — Animated KPI cards, Chart.js bar + doughnut charts
- 👥 **Employees** — Full CRUD with modal forms, dependent viewer, avatar initials
- 🏢 **Departments** — Department cards, manager assignment, headcount tracking
- 📁 **Projects** — Project tracking with team viewer and hour progress bars
- 📈 **Reports** — Aggregates (SUM, AVG, MAX, MIN), subqueries, workload filters, top performers
- 🔍 **DataTables** — Sortable, searchable, paginated tables on every page
- 🗑️ **SweetAlert2** — Sleek confirmation dialogs for destructive actions
- 🌑 **Dark Enterprise UI** — Slate + Blue corporate theme, fully responsive

---

## 🗂️ Project Structure

```
ems/
├── index.php                  # Entry point — redirects to login or dashboard
├── login.php                  # Authentication page
├── logout.php                 # Session destroy + redirect
├── config.php                 # PDO database connection + shared helpers
│
├── dashboard.php              # Main dashboard — live stats, charts, employee table
├── employees.php              # Employees — list, add, edit, delete, dependents
├── departments.php            # Departments — cards, add, edit, delete
├── projects.php               # Projects — cards, team viewer, add, edit, delete
├── reports.php                # Reports — analytics, filters, aggregates
│
├── delete_employee.php        # AJAX endpoint: delete employee
├── delete_department.php      # AJAX endpoint: delete department
├── delete_project.php         # AJAX endpoint: delete project
├── get_dependents.php         # AJAX endpoint: fetch employee dependents
├── get_project_team.php       # AJAX endpoint: fetch project team members
│
└── includes/
    ├── style.php              # Shared dark CSS theme (injected via include)
    ├── scripts.php            # CDN scripts + shared JS helpers (SweetAlert, tooltips)
    ├── sidebar.php            # Navigation sidebar partial
    └── topbar.php             # Top header bar partial
```

---

## 🗃️ Database Schema

The system uses **6 core tables** and **3 views**:

| Table | Description |
|---|---|
| `employee` | Staff records — name, email, address, dept, supervisor |
| `department` | Divisions with manager reference |
| `project` | Projects linked to departments |
| `works_on` | Many-to-many: employees ↔ projects + hours |
| `dependent` | Employee family/dependent records |
| `login` | Authentication — username, password, role |

| View | Description |
|---|---|
| `employeedetails` | Employee + department name join |
| `projectemployees` | Employee + project + hours join |
| `departmentprojects` | Department + project join |

### Entity Relationship Summary

```
Department  1──M  Employee  M──M  Project
                     │
                     1
                     │
                     M
                 Dependent

Employee  1──1  Login
Department  1──1  Manager (Employee)
Employee  1──1  Supervisor (Employee, self-reference)
```

---

## ⚙️ Requirements

Before you begin, make sure you have the following installed:

| Tool | Purpose | Download |
|---|---|---|
| **XAMPP** | Local Apache + MySQL server | [apachefriends.org](https://www.apachefriends.org/) |
| **Web Browser** | Chrome, Firefox, or Edge | Any modern browser |
| **Git** *(optional)* | Clone the repository | [git-scm.com](https://git-scm.com/) |

> XAMPP includes Apache, MySQL/MariaDB, and PHP in one installer — it's everything you need.

---

## 🚀 Installation & Setup

### Step 1 — Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer and install to the default path (`C:\xampp` on Windows)
3. Open the **XAMPP Control Panel**
4. Start both **Apache** and **MySQL** by clicking their **Start** buttons — both status indicators should turn green

---

### Step 2 — Get the Project Files

**Option A — Clone with Git:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git ems
```

**Option B — Download ZIP:**
1. Click the green **Code** button on this GitHub page
2. Select **Download ZIP**
3. Extract the ZIP
4. Move the extracted folder into `C:\xampp\htdocs\`
5. Rename the folder to `ems` if it isn't already

Your project should now be at:
```
C:\xampp\htdocs\ems\
```

---

### Step 3 — Create the Database

1. Open your browser and go to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click **New** in the left sidebar
3. Enter the database name:
   ```
   employee_management_system
   ```
4. Click **Create**
5. Click on your new database in the left sidebar to select it
6. Click the **Import** tab at the top
7. Click **Choose File** and select the SQL file from this project:
   ```
   ems/employee_management_system.sql
   ```
8. Scroll down and click **Import**
9. You should see a success message — all tables and sample data are now loaded

---

### Step 4 — Configure the Database Connection

Open the file `ems/config.php` in any text editor (Notepad, VS Code, etc.) and confirm the settings match your XAMPP setup:

```php
define('DB_HOST', 'localhost');       // Leave as localhost for XAMPP
define('DB_NAME', 'employee_management_system');  // Must match the DB you created
define('DB_USER', 'root');            // Default XAMPP MySQL username
define('DB_PASS', '');                // Default XAMPP MySQL password is empty
```

> ⚠️ If you set a custom MySQL password during XAMPP setup, enter it in `DB_PASS`.

---

### Step 5 — Open in Browser

With Apache and MySQL running in XAMPP, open:

```
http://localhost/ems/
```

You will be automatically redirected to the login page.

---

## 🔐 Login Credentials

| Role | Username | Password | Access |
|---|---|---|---|
| **Admin** | `admin` | `1234` | Full CRUD — add, edit, delete everything |
| **Employee** | `ahmed` | `1234` | Read-only — view data only |
| **Employee** | `usman` | `1234` | Read-only — view data only |

> Admins see all action buttons. Employee accounts see the same pages but without add/edit/delete controls.

---

## 📋 Pages & Functionality

### 🏠 Dashboard (`dashboard.php`)
- Animated counters for Total Employees, Departments, Projects, Hours Logged
- **Bar Chart** — Headcount distribution per department
- **Doughnut Chart** — Resource allocation (hours per project)
- DataTables employee directory with live search
- Active project cards with progress bars
- Department headcount breakdown + activity log

### 👥 Employees (`employees.php`)
- Full searchable, sortable, paginated DataTable
- **Add Employee** — modal form with name, email, address, department, supervisor
- **Edit Employee** — pre-filled modal (opens from Edit button or `?edit=ID`)
- **Delete Employee** — SweetAlert2 confirmation, cascades to login/dependents/works_on
- **View Dependents** — popup modal showing family members on record

### 🏢 Departments (`departments.php`)
- Visual department cards with color-coded icons, manager name, headcount
- Overview DataTable with full details
- **Add/Edit/Delete** departments via modal — admin only
- Manager assignment from existing employee list

### 📁 Projects (`projects.php`)
- Project cards with stats boxes (total hours, team size), progress bars
- **View Team** — popup listing all assigned employees and their hours
- **Add/Edit/Delete** projects — admin only
- Full DataTable with department, hours, and team size columns

### 📈 Reports (`reports.php`)
- **Top Performers** — ranked cards by total hours logged
- **Workload Filter** — filter employees by minimum hours (HAVING clause) and department
- **Project Summary Table** — SUM, AVG, MAX, MIN hours per project
- **Unassigned Employees** — NOT IN subquery finding staff with no project
- **Department Productivity** — multi-table JOIN with GROUP BY
- **Dual Charts** — grouped bar (hours + headcount) and doughnut (hours share)
- Print button for report export

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, Bootstrap 5.3, Vanilla JavaScript |
| **Charts** | Chart.js 4.4 |
| **Tables** | DataTables 1.13 + Bootstrap 5 theme |
| **Dialogs** | SweetAlert2 11 |
| **Backend** | PHP 8.x (PDO, sessions, prepared statements) |
| **Database** | MySQL / MariaDB (via XAMPP) |
| **Server** | Apache (via XAMPP) |

---

## 🧩 SQL Concepts Demonstrated

| Concept | Where Used |
|---|---|
| `CREATE TABLE` with FK constraints | `employee_management_system.sql` |
| `INSERT`, `UPDATE`, `DELETE` | All CRUD pages |
| `JOIN` (INNER, LEFT) | Dashboard, Reports, all data fetches |
| `GROUP BY` + `HAVING` | Reports — workload filter |
| Subqueries (`NOT IN`, `IN`) | Reports — unassigned employees |
| `SUM`, `AVG`, `MAX`, `MIN`, `COUNT` | Reports — project metrics |
| `ORDER BY`, `LIMIT` | Dashboard — recent employees, top performers |
| `LIKE`, `DISTINCT` | Available via DataTables search |
| Views (`CREATE VIEW`) | `employeedetails`, `projectemployees`, `departmentprojects` |
| Indexes | On `Email`, `Name`, `Dept_ID`, `Hours` |
| Self-referencing FK | `Employee.Supervisor_ID → Employee.Emp_ID` |
| Cascading deletes | `ON DELETE CASCADE` on dependent, login, works_on |

---

## 🔒 Security Notes

- All database queries use **PDO prepared statements** — protected against SQL injection
- User input is sanitized with `htmlspecialchars()` before output — XSS protection
- Pages are protected with `requireLogin()` / `requireAdmin()` session checks
- AJAX delete endpoints verify admin role before executing

> ⚠️ **Passwords in the demo database are plain-text (`1234`).** For a production deployment, replace with `password_hash()` on insert and `password_verify()` on login. The `config.php` includes a comment marking where to do this.

---

## 🐛 Troubleshooting

**Blank page or errors on load**
- Make sure both **Apache** and **MySQL** are running (green) in the XAMPP Control Panel
- Check that the folder is inside `C:\xampp\htdocs\ems\` not a subfolder inside a subfolder

**"Connection failed" error**
- Open `config.php` and verify `DB_NAME` exactly matches your database name in phpMyAdmin
- Confirm `DB_USER` is `root` and `DB_PASS` is empty (default XAMPP)

**Tables are empty / no data**
- Make sure you imported the `.sql` file into the correct database in phpMyAdmin
- Go to phpMyAdmin → select `employee_management_system` → check that tables like `employee` and `department` have rows

**Login not working**
- Confirm the `login` table has rows (check in phpMyAdmin)
- Use `admin` / `1234` exactly as written — no spaces

**CSS/JS not loading**
- All styles and scripts are loaded from CDNs — make sure you have an internet connection
- If offline, you'll need to download Bootstrap, Chart.js, DataTables, and SweetAlert2 locally

---

## 📄 License

This project was created for academic purposes (university database course — Section BAI-4A). Free to use and modify for educational use.

---

## 👤 Author

**Zafar Ullah Khan** — Reg# 24P-0021  
University Database Project — Employee Management System  
Section BAI-4A
