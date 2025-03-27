# Allow admins to set subscriber status when they create a new user in WP admin

## Add a Select Box to the “Add New User” Form

**Location:**  
WP Admin > Users > Add New User

### Label
**MailPoet Subscriber Status**

### Values
- **Subscribed**
- **Unconfirmed** (will receive a confirmation email)
- **Unsubscribed**

### Notes
- The **default value** is **Unconfirmed**/**Unsubscribed**
- **Unconfirmed** is present **only when confirmation is enabled**

### On Form Submission
- Ensure the subscriber is added with the **correct status**
- For existing subscribers (who were not WP users), **update the global status**
- If admin selects **Unconfirmed**, also **send the confirmation email**

> ⚠️ Do **not** display this field in the **user edit form**