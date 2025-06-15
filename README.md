# Sellup

Sellup is a consumer-to-consumer (C2C) web platform designed to support South Africans participating in the informal economy. Amidst high unemployment, Sellup provides an accessible, user-friendly space for individuals to advertise, discover, and evaluate listings online.

## 🌍 Live Demo
Visit the live site: [https://sellups.onrender.com](https://sellups.onrender.com)

## 📚 Overview

Many existing C2C platforms in South Africa offer poor usability. Sellup was built to address this gap using a modern 3-tier architecture that separates the client-side interface, business logic, and data storage.

### Key Technologies:
- **Frontend**: HTML, JavaScript, Bootstrap
- **Backend**: PHP (REST-like structure)
- **Database**: MySQL
- **AI Integration**: Google Gemini API for listing evaluation
- **Architecture**: 3-tier (frontend, backend, database)


## 🚀 Getting Started

### Prerequisites
- Docker installed
- MySQL server
- Google Gemini API key (optional, for listing evaluation)

---

### 🔧 Local Setup (Docker)

1. Clone the repository:
   \`\`\`bash
   git clone https://github.com/GrantODP/sellup.git
   cd sellup
   \`\`\`

2. Build Docker image:
   
   docker build -t sellup-app 
   

3. Run the container:
   
   docker run -d -p 8080:80 sellup-app
   

4. Access the app at: \`http://localhost:8080\`

---

### 🛠️ Database Setup

1. Switch to the \`database\` branch:

2. Download the \`c2cfinal.sql\` file.

3. Import into your MySQL server:
   
   mysql -u your_user -p your_database < c2cfinal.sql
   

4. Configure database connection:
   - Open \`backend/config/sys_config.php\`
   - Set your DB host, username, password, and database name.

---

### 🤖 AI Evaluation Setup

1. Get a Gemini API key from [Google AI Studio](https://makersuite.google.com).
2. Open \`backend/config/sys_config.php\`
3. Add your API key like so:

## ➕ Adding Pages to the Site

There are two ways to add new pages:

### Method 1: Public Directory
- Add a \`.html\` or \`.php\` file directly in \`public/\`

### Method 2: Views with Routing
1. Add your file to \`frontend/views/\` (e.g., \`mypage.php\`)
2. Add a function to \`AdController.php\`:
   \`\`\`php
   return Views::get_view('mypage.php');
   \`\`\`
3. Register a route in \`index.php\`:
   \`\`\`php
   $router->add_get('/mypage', PageController::my_function);
   \`\`\`




