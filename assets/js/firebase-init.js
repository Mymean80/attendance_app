// Import the functions needed from Firebase
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyAUoesykMDt0B0iYb1kXLbWut8ns-S5ZOM",
  authDomain: "attendance-app-4124d.firebaseapp.com",
  projectId: "attendance-app-4124d",
  storageBucket: "attendance-app-4124d.firebasestorage.app",
  messagingSenderId: "761752063017",
  appId: "1:761752063017:web:e5a7c976d2a2340cc16038",
  measurementId: "G-ZB39V62528"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);

export { app, analytics }; 