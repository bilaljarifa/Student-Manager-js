// // notification-service.js

// import { Notification } from "./notification.js";

// export class NotificationService {
//     constructor() {
//         this.notifications = this.loadNotifications();
//     }

//     // ── CREATE ──────────────────────────

//     addNotification(title, message, type = "info") {
//         const notification = new Notification(
//             Date.now(),
//             title,
//             message,
//             type,
//             Date.now()
//         );

//         this.notifications.unshift(notification);  // Add to beginning
//         this.saveNotifications();

//         return notification;
//     }

//     // ── READ ────────────────────────────

//     getAllNotifications() {
//         return this.notifications;
//     }

//     getUnreadCount() {
//         return this.notifications.filter(n => !n.read).length;
//     }

//     // ── UPDATE ──────────────────────────

//     markAsRead(notificationId) {
//         const notification = this.notifications.find(n => n.id === notificationId);
//         if (notification) {
//             notification.markAsRead();
//             this.saveNotifications();
//         }
//     }

//     markAllAsRead() {
//         this.notifications.forEach(n => n.markAsRead());
//         this.saveNotifications();
//     }

//     // ── DELETE ──────────────────────────

//     deleteNotification(notificationId) {
//         this.notifications = this.notifications.filter(n => n.id !== notificationId);
//         this.saveNotifications();
//     }

//     clearAllNotifications() {
//         this.notifications = [];
//         this.saveNotifications();
//     }

//     // ── STORAGE ─────────────────────────

//     saveNotifications() {
//         localStorage.setItem("notifications", JSON.stringify(this.notifications));
//     }

//     loadNotifications() {
//         const data = localStorage.getItem("notifications");
//         return data ? JSON.parse(data) : [];
//     }
// }