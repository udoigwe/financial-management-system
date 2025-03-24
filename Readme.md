Financial Management System

The Financial Management System is a banking application designed for customers to manage their accounts, track transactions, and set and achieve budgeting goals. In addition to account features, the system integrates a budgeting feature that analyzes customer spending habits, classifies them into spending categories, and enforces spending limits with notification triggers. Bank employees, including account officers, serve as points of contact for customer support.

Installation Guide

Prerequisites

Ensure you have the following installed on your system:

XAMPP (for Apache and MySQL)

Git

Visual Studio Code (VS Code)

1. Clone the Repository

Copy this repository link:

https://github.com/udoigwe/financial-management-system.git

Create a folder in the htdocs folder of your XAMPP and name it finhive.

Open finhive in Visual Studio Code (VS Code).

Open a new terminal in VS Code and run the following command:

git clone https://github.com/udoigwe/financial-management-system.git .

Note: Pay attention to the space and the dot (.) at the end of the command. This ensures the repository is cloned directly into the finhive folder without creating an additional subfolder.

2. Database Setup

Start XAMPP.

Open phpMyAdmin and create a new database named fms.

Locate the SQL SCRIPTS folder in the root directory of the project.

Import the fms.sql schema file into the newly created database.

3. Start the Application

Ensure that Apache and MySQL services are running in XAMPP.

Open a web browser of your choice.

Navigate to:

http://localhost/finhive

Your Financial Management System should now be running successfully on your local server.

Need Help?

If you encounter any issues, feel free to raise an issue in the repository or reach out to the project maintainers.

License

This project is licensed under the MIT License.

Author

Developed by DBMS team 2025.
