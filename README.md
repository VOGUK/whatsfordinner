# 🍽️ What's for dinner?!

A lightweight, mobile-first web application designed to take the stress out of meal planning and grocery shopping. 

This app allows users to maintain a **Master List** of frequent items, build a custom **Shopping List** using an intuitive drag-and-drop or button-based interface, and plan out a **Weekly Menu**.

## ✨ Features

- **📱 Mobile Optimized:** Specifically designed to work perfectly on devices like the iPhone XR, with large touch targets and a responsive, stacked layout.
- **🏠 PWA Ready:** Supports "Add to Home Screen" on both iOS and Android, featuring a custom app icon and standalone display mode.
- **📝 List Management:** - Manage a permanent Master List of groceries.
  - Quickly add items to your active Shopping List.
  - Reorder or delete shopping items on the fly.
- **📅 Weekly Planner:** Map out your meals for the week with a dedicated menu planner.
- **📥 Export & Share:** - Export your lists and menus to **PDFs**.
  - **Email** your shopping list directly to your inbox.
  - **Email** your weekly menu directly to family members.
- **🔐 Secure Access:** Built-in authentication system with user management and password controls.
- **🌓 Dark Mode:** Full support for system-wide light and dark themes.

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3 (Custom Properties & Media Queries), Vanilla JavaScript.
- **Backend:** PHP 8.x.
- **Database:** SQLite (Single-file database for easy portability).
- **Libraries:** [html2pdf.js](https://github.com/eKoopmans/html2pdf.js) for document generation.

## 🚀 Installation & Setup

1. **Clone the repository** to your local machine or web server.
2. **Ensure your server supports PHP 7.4+** and has the SQLite extension enabled.
3. **Set Permissions:** Make sure the root directory is "writable" so the app can create and update the `whatsfordinner.sqlite` file.
4. **Access the App:** Navigate to the folder in your browser.
   - *Default Username:* `admin`
   - *Default Password:* `password123` (It is highly recommended to change this in the System settings after your first login).

## 📲 Adding to Home Screen

This app is a **Progressive Web App (PWA)**. To install it:
- **On Android (Chrome):** Tap the **Three-dot menu** -> **Install App** or **Add to Home screen**.
- **On iOS (Safari):** Tap the **Share** icon -> **Add to Home Screen**.

## 🛡️ Privacy & Security

The database (`whatsfordinner.sqlite`) is excluded from this repository via `.gitignore` to protect personal data. If you are deploying this yourself, the app will automatically generate a fresh database upon first use or require a blank skeleton file to be provided.

---
*Created with ❤️ to solve the age-old question: "What's for dinner?"*
