# 3-kursa-projekts
Vienuviet pieejams plānotājs personīgiem un profesionāliem mērķiem.​
Atgādinājumu funkcija palīdz saglabāt svarīgus notikumus un termiņus.​
Ērta izdevumu un pirkumu pārraudzība.​

## Sistēmas palaišanas vadlīnijas

### Priekšnosacījumi
- Node.js
- PHP 
- Composer
- MySQL

### Backend (Laravel) palaišana
1. Navigējiet uz backend direktoriju (backend)
2. Instalējiet dependences:
   ```bash
   composer install
   ```
3. Nokopējiet `.env.example` uz `.env` un konfigurējiet datubāzes iestatījumus
4. Ģenerējiet aplikācijas atslēgu:
   ```bash
   php artisan key:generate
   ```
5. Palaidiet migrācijas:
   ```bash
   php artisan migrate
   ```
6. Startējiet serveri:
   ```bash
   php artisan serve
   ```

### Frontend (Vue.js) palaišana
1. Navigējiet uz frontend direktoriju (laika_planotajs)
2. Instalējiet dependences:
   ```bash
   npm install
   ```
3. Palaidiet izstrādes serveri:
   ```bash
   npm run dev
   ```

## Sistēmas izstrādes rīku saraksts

### Frontend
- **Vue.js** - JavaScript framework lietotāja interfeisa izstrādei
- **Vue Router** - maršrutēšanas sistēma
- **Vuex/Pinia** - stāvokļa pārvaldība
- **Axios** - HTTP klientam API pieprasījumiem
- **Vite** - būvēšanas rīks un izstrādes serveris

### Backend
- **Laravel** - PHP web aplikāciju framework
- **Eloquent ORM** - datubāzes darbībām
- **Laravel Sanctum** - API autentifikācija
- **Laravel Migration** - datubāzes shēmas pārvaldība

### Datubāze
- **MySQL** - relāciju datubāze

### Izstrādes rīki
- **Composer** - PHP dependency manager
- **npm/yarn** - JavaScript package manager
- **Git** - versijas kontrole
- **Postman/Insomnia** - API testēšana

## Projekta statuss

### Izdarītais:
- Login, register, logout pabeigts
- Home page iesākts (vēl mainīsies)
- Navigation bar iesākts (vēl mainīsies)
- Kalendārs pabeigts
- Projektu plānotājs ir pabeigts
- Finanšu sekošanai pabeigta
- Iestatījumi pabeigti

### Projekta testi
Pievienots test fails "Projekta testi.xlsx"

### Testi prieks PD
Pievienots test fails Test.pdf