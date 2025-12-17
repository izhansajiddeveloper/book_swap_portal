# Book Swap Portal

**Book Swap Portal** is a web-based application that allows users to share, swap, and manage books online. Users can add their books, browse other users’ books, send swap requests, and track the status of their swaps. It is designed to make book exchange easy, interactive, and organized.

![image alt](https://github.com/izhansajiddeveloper/book_swap_portal/blob/832a038c39dea3c2f19e86950aa2a2dd8bf9ba2f/web4.png)

---

## **Table of Contents**

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Database Structure](#database-structure)
- [Installation](#installation)
- [Usage](#usage)
- [Screenshots](#screenshots)
- [License](#license)

---

## **Features**

- User Registration and Login  
![image alt](https://github.com/izhansajiddeveloper/book_swap_portal/blob/234913960cb6d55c1660ee6b44663261601630ce/web5.png)

- Add, edit, and manage your books  
- Browse books added by other users  
- Send and receive swap requests  
- Accept or reject swap requests  
- Track the status of swapped books  
- User dashboard with book and request statistics  
![image alt](https://github.com/izhansajiddeveloper/book_swap_portal/blob/be79c358e224acb89e11d1704029e4ce9da5b701/web6.png)

- Admin panel for managing users and books  
![Admin Dashboard](https://github.com/izhansajiddeveloper/book_swap_portal/blob/1c35353c5dd7206d32e61ff40536aa98d3dbd5cf/WEB7.png)

- Book approval workflow for admin moderation

---

## **Technology Stack**

- **Frontend:** HTML, CSS, JavaScript  
- **Backend:** PHP  
- **Database:** MySQL  
- **Server:** XAMPP / Apache  

---

## **Database Structure**

### **Tables**

1. **users**
   - `user_id` (PK) - Unique ID for each user  
   - `name` - User's full name  
   - `email` - User's email address  
   - `password` - Encrypted password  
   - `created_at` - Account creation timestamp  

2. **books**
   - `book_id` (PK) - Unique ID for each book  
   - `user_id` (FK) - Reference to the owner user  
   - `title` - Book title  
   - `author` - Book author  
   - `genre` - Book genre  
   - `description` - Short description of the book  
   - `image` - Book image filename/path  
   - `status` - `available` or `swapped`  
   - `approved` - `yes` or `no`  
   - `book_condition` - Condition of the book (e.g., New, Used)  
   - `created_at` - Timestamp of book addition  

3. **swap_requests**
   - `swap_id` (PK) - Unique ID for each swap request  
   - `requester_id` (FK) - ID of the user requesting the swap  
   - `book_id` (FK) - ID of the requested book  
   - `status` - `pending`, `accepted`, or `rejected`  
   - `created_at` - Timestamp of the request  

---

## **Installation**

1. Clone the repository:

```bash
git clone https://github.com/izhansajiddeveloper/book_swap_portal.git
