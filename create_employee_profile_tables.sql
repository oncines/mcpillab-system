-- Employee Profile Tables for MCPIL Pharmaceutical Laboratory Management System

USE mcpillab;

-- Employee Profiles table
CREATE TABLE IF NOT EXISTS employee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    employee_id VARCHAR(20) UNIQUE,
    photo VARCHAR(255),
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    phone VARCHAR(20),
    place_of_birth VARCHAR(100),
    birth_date DATE,
    marital_status ENUM('single', 'married', 'widowed', 'divorced') DEFAULT NULL,
    religion VARCHAR(50),
    citizen_address TEXT,
    residential_address TEXT,
    ec_name VARCHAR(100), -- Emergency contact name
    ec_relationship VARCHAR(50), -- Emergency contact relationship
    ec_phone VARCHAR(20), -- Emergency contact phone
    department VARCHAR(50),
    date_hired DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    basic_salary DECIMAL(10,2),
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    tax_id VARCHAR(20),
    sss_number VARCHAR(20),
    philhealth VARCHAR(20),
    pagibig VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Employee Education table
CREATE TABLE IF NOT EXISTS employee_education (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    degree VARCHAR(100),
    school VARCHAR(100),
    field VARCHAR(100),
    gpa DECIMAL(3,2),
    year_start INT,
    year_end INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Employee Family table
CREATE TABLE IF NOT EXISTS employee_family (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    family_type ENUM('spouse', 'child', 'parent', 'sibling', 'other') DEFAULT NULL,
    person_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
