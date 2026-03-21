# Arquitectura del proyecto

## Opciones evaluadas

Se analizaron 5 estructuras para el backend y 5 para el frontend.
La selección se hizo con un criterio claro: **sin leer una línea de código, solo viendo los nombres de las carpetas, ya se sabe qué hace cada parte de la aplicación**.

---

## Backend — 5 opciones

### Opción A — Capas por responsabilidad ✅ ELEGIDA

```
backend/
├── public/
│   └── index.php
├── config/
│   └── app.php
├── app/
│   ├── Controllers/
│   │   └── SubmissionController.php
│   ├── Services/
│   │   ├── Database.php
│   │   └── FileUploader.php
│   ├── Validation/
│   │   ├── Validator.php
│   │   └── SubmissionValidator.php
│   └── Http/
│       ├── Response.php
│       └── Router.php
└── Dockerfile
```

### Opción B — MVC clásico

```
backend/
├── public/
│   └── index.php
├── config/
│   └── database.php
├── app/
│   ├── Models/
│   │   └── Submission.php
│   ├── Views/
│   │   └── (json responses)
│   └── Controllers/
│       └── SubmissionController.php
├── routes/
│   └── api.php
└── Dockerfile
```

### Opción C — Hexagonal / Ports & Adapters

```
backend/
├── public/
│   └── index.php
├── src/
│   ├── Domain/
│   │   ├── Submission.php
│   │   └── SubmissionRepositoryInterface.php
│   ├── Application/
│   │   └── CreateSubmissionUseCase.php
│   └── Infrastructure/
│       ├── MySqlSubmissionRepository.php
│       ├── HttpRouter.php
│       └── FileStorage.php
└── Dockerfile
```

### Opción D — Feature-based (vertical slices)

```
backend/
├── public/
│   └── index.php
├── features/
│   └── submission/
│       ├── SubmissionController.php
│       ├── SubmissionValidator.php
│       ├── SubmissionRepository.php
│       └── SubmissionResponse.php
├── shared/
│   ├── Database.php
│   └── Router.php
└── Dockerfile
```

### Opción E — Flat + prefijos

```
backend/
├── public/
│   └── index.php
├── src/
│   ├── config.php
│   ├── http-response.php
│   ├── http-router.php
│   ├── db-connection.php
│   ├── validator.php
│   ├── submission-controller.php
│   ├── submission-validator.php
│   └── file-uploader.php
└── Dockerfile
```

---

## Frontend — 5 opciones

### Opción 1 — Flat modules

```
frontend/src/
├── app.js
├── state.js
├── router.js
├── dom.js
├── icons.js
├── fields.js
├── config.js
├── api.js
├── validation.js
└── views/
    ├── intro.js
    ├── personal.js
    ├── personal2.js
    └── ...
```

### Opción 2 — Atomic Design

```
frontend/src/
├── app.js
├── atoms/
│   ├── Button.js
│   ├── Input.js
│   └── Select.js
├── molecules/
│   ├── FieldGroup.js
│   └── PhoneInput.js
├── organisms/
│   ├── PersonalForm.js
│   └── ProgressBar.js
├── pages/
│   ├── IntroPage.js
│   └── SuccessPage.js
└── state/
    └── store.js
```

### Opción 3 — Feature folders

```
frontend/src/
├── app.js
├── features/
│   ├── intro/
│   │   └── IntroView.js
│   ├── personal/
│   │   ├── PersonalView.js
│   │   └── personalValidation.js
│   ├── education/
│   │   ├── EducationView.js
│   │   └── educationValidation.js
│   └── ...
└── shared/
    ├── router.js
    ├── state.js
    └── ui/
```

### Opción 4 — Framework + Steps ✅ ELEGIDA

```
frontend/src/
├── app.js
├── framework/
│   ├── createElement.js
│   ├── router.js
│   └── store.js
├── ui/
│   ├── fields.js
│   ├── icons.js
│   └── progress-bar.js
├── steps/
│   ├── 0-intro.js
│   ├── 1-contact.js
│   ├── 2-location.js
│   ├── 3-education.js
│   ├── 4-experience.js
│   ├── 5-consent.js
│   └── results.js
├── services/
│   ├── api.js
│   └── validation.js
└── data/
    ├── options.js
    └── partners.js
```

### Opción 5 — Monorepo-style con packages

```
frontend/
├── packages/
│   ├── core/
│   │   ├── dom.js
│   │   ├── router.js
│   │   └── state.js
│   ├── ui-kit/
│   │   ├── Button.js
│   │   ├── Input.js
│   │   └── Select.js
│   └── forms/
│       ├── steps/
│       └── validation/
└── app/
    └── main.js
```

---

## Por qué estas y no las otras

### Backend: Opción A (Capas por responsabilidad)

**Lo que transmite a primera vista:**

Con solo ver `Controllers/`, `Services/`, `Validation/`, `Http/` se entiende inmediatamente cómo fluye una petición: entra por Http, se valida en Validation, se procesa en Controllers, y usa Services para persistencia y archivos. No hay ambigüedad.

**Por qué no las demás:**

- **MVC (B)**: La carpeta `Views/` no tiene sentido real en una API JSON. Tener una carpeta vacía o con "responses" es confuso.
- **Hexagonal (C)**: `Domain/`, `Application/`, `Infrastructure/` son conceptos potentes pero desproporcionados para un formulario con un solo endpoint. Parece que se está intentando impresionar con palabras en vez de con claridad.
- **Feature-based (D)**: Con una sola feature (`submission`), tener `features/submission/` es una carpeta wrapper sin valor. Brilla cuando hay 10+ features, pero aquí solo añade profundidad innecesaria.
- **Flat + prefijos (E)**: Escala mal. Con 8 archivos funciona, con 20 se convierte en una lista ilegible donde hay que leer cada nombre para encontrar algo.

### Frontend: Opción 4 (Framework + Steps)

**Lo que transmite a primera vista:**

Los archivos `0-intro.js`, `1-contact.js`, `2-location.js`... cuentan la historia del formulario en orden. Cualquier persona sabe que es un flujo de pasos numerados sin abrir un solo archivo. La carpeta `framework/` separa claramente lo que es "infraestructura interna" de lo que es "lógica de negocio" en `steps/`.

**Por qué no las demás:**

- **Flat modules (1)**: `personal.js`, `personal2.js`... no dice nada sobre el orden ni el propósito. ¿Qué es `personal2`? Hay que abrir el archivo para saber que es "ubicación".
- **Atomic Design (2)**: `atoms/`, `molecules/`, `organisms/` son niveles de abstracción que requieren un framework de componentes real (React, Vue). En vanilla JS sin virtual DOM, crear un `Button.js` atómico es ceremonia sin beneficio.
- **Feature folders (3)**: Cada feature tiene un solo archivo de vista y posiblemente uno de validación. Crear carpetas individuales (`personal/`, `education/`) para 1-2 archivos cada una es ruido visual.
- **Monorepo packages (5)**: `packages/core/`, `packages/ui-kit/`... es infraestructura de un proyecto con múltiples aplicaciones. Para un solo formulario, es como construir un aeropuerto para un avión de papel.

---

## Principio rector

> La mejor arquitectura es la que hace que la siguiente persona que abra el proyecto piense "tiene sentido" en los primeros 5 segundos.

Las opciones elegidas no son las más sofisticadas ni las más simples. Son las que mejor equilibran **claridad inmediata**, **separación de responsabilidades** y **proporcionalidad al tamaño del proyecto**.

---

## Observabilidad y entrega

El diseño anterior cubre el **dominio** (formulario, API, seguridad). Encima se añadieron piezas de **operación** sin mezclarlas con la lógica de negocio:

| Pieza | Rol |
|-------|-----|
| `GET /api/health` | Contrato estable para balanceadores y orquestadores (BBDD + PHP + disco). |
| `X-Request-ID` / logs JSON | Correlación Nginx ↔ PHP para auditoría y soporte. |
| `Makefile` + CI | Misma experiencia en local y en GitHub Actions. |
| Accesibilidad (skip link, foco, modal) | Calidad de producto sin cambiar la arquitectura por capas. |
