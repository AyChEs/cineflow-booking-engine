import './bootstrap';

import Alpine from 'alpinejs';
import { enfocarPrimerError, iniciarApariciones, iniciarMenu } from './menu';

window.Alpine = Alpine;

Alpine.start();

iniciarMenu();
iniciarApariciones();
enfocarPrimerError();
