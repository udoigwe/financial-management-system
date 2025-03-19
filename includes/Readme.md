Financial Management System

A financial management system for tracking and managing financial records efficiently.

Installation Guide

Prerequisites

Ensure you have the following installed on your system:

XAMPP (for Apache and MySQL)

Git

Visual Studio Code (VS Code)

1. Clone the Repository

Copy this repository link:

https://github.com/udoigwe/financial-management-system.git

Create a folder in a preferred location on your PC and name it finhive.

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

Developed by Udo Igwe.
