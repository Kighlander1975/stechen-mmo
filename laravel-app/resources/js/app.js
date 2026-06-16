import './bootstrap';

import Alpine from 'alpinejs';
import { mountVueIslands } from './vue-islands';

window.Alpine = Alpine;

Alpine.start();
mountVueIslands();
