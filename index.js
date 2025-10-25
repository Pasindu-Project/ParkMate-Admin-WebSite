// OOP Class to handle video and overlay
class PageHandler {
    constructor(videoId, overlayId, buttonId) {
        this.video = document.getElementById(videoId);
        this.overlay = document.getElementById(overlayId);
        this.button = document.getElementById(buttonId);

        this.init();
    }

    // Initialize all behaviors
    init() {
        this.setupButton();
        this.startVideo();
    }

    startVideo() {
        if (this.video) {
            this.video.play().catch(err => console.log("Autoplay prevented:", err));
        }
    }

    toggleOverlay(show) {
        if (this.overlay) {
            this.overlay.style.display = show ? "block" : "none";
        }
    }

    setupButton() {
        if (this.button) {
            this.button.addEventListener("mouseenter", () => this.toggleOverlay(false));
            this.button.addEventListener("mouseleave", () => this.toggleOverlay(true));
        }
    }
}

// Create an instance of PageHandler
const page = new PageHandler("bg-video", "overlay", "dashboardBtn");
