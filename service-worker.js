self.addEventListener('push', function(event) {
    const data = event.data.json();
    const title = data.title;
    const options = {
        body: data.body,
        icon: 'icon.png',
        badge: 'icon.png'
    };

    event.waitUntil(self.registration.showNotification(title, options));
});
