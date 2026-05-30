// // notification.js

// export class Notification {
//     constructor(id, title, message, type, timestamp, read = false) {
//         this.id = id;
//         this.title = title;           // "Student Added", "Grade Updated", etc.
//         this.message = message;       // "John Doe has been added"
//         this.type = type;             // "success", "warning", "error", "info"
//         this.timestamp = timestamp;   // Date.now()
//         this.read = read;             // Has user seen it?
//     }

//     // ── METHODS ──────────────────────

//     // Mark notification as read
//     markAsRead() {
//         this.read = true;
//     }

//     // Get time since notification was created
//     getTimeAgo() {
//         const now = Date.now();
//         const diff = now - this.timestamp;
//         const seconds = Math.floor(diff / 1000);
//         const minutes = Math.floor(seconds / 60);
//         const hours = Math.floor(minutes / 60);
//         const days = Math.floor(hours / 24);

//         if (seconds < 60) return "just now";
//         if (minutes < 60) return `${minutes}m ago`;
//         if (hours < 24) return `${hours}h ago`;
//         return `${days}d ago`;
//     }

//     // Format for display
//     toHTML() {
//         return `
//             <div class="notification-item ${this.type} ${this.read ? 'read' : 'unread'}">
//                 <div class="notification-content">
//                     <h4>${this.title}</h4>
//                     <p>${this.message}</p>
//                     <span class="notification-time">${this.getTimeAgo()}</span>
//                 </div>
//                 <div class="notification-actions">
//                     ${!this.read ? `<button class="mark-read-btn" data-id="${this.id}">✓</button>` : ''}
//                     <button class="delete-notification-btn" data-id="${this.id}">✕</button>
//                 </div>
//             </div>
//         `;
//     }
// }