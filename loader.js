// OOP class to handle the loader
class Loader {
    constructor(redirectUrl, delay) {
        this.redirectUrl = redirectUrl; // URL to redirect after loading
        this.delay = delay;             // Delay in milliseconds
    }

    // Start the loader
    start() {
        console.log("Loader started...");
        setTimeout(() => this.redirect(), this.delay);
    }

    // Redirect method
    redirect() {
        window.location.href = this.redirectUrl;
    }
}

// Create a loader instance and start it
const pageLoader = new Loader("login.php", 5000);
pageLoader.start();
