import './bootstrap';
import 'bootstrap';
import { Toast } from 'bootstrap';
import './components/searchable-select';
import './password-toggle';


document.addEventListener('DOMContentLoaded', () => {
    const flashToasts = document.querySelectorAll('[data-flash-toast]');

    flashToasts.forEach((element) => {
        Toast.getOrCreateInstance(element).show();
    });
});