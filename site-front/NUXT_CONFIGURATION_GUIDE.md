# Nuxt.js Configuration and Assets Guide

This guide provides comprehensive instructions for configuring Nuxt.js and managing assets in this project. It's designed to help AI agents and developers understand the project structure and configuration.

## Table of Contents

- [Project Overview](#project-overview)
- [Project Structure](#project-structure)
- [Nuxt Configuration](#nuxt-configuration)
- [Environment Variables](#environment-variables)
- [Asset Management](#asset-management)
- [Module Configuration](#module-configuration)
- [Runtime Configuration](#runtime-configuration)
- [Development Setup](#development-setup)
- [Common Configuration Tasks](#common-configuration-tasks)
- [Troubleshooting](#troubleshooting)

## Project Overview

This is a Nuxt 3 application with the following key features:

- **SSR Enabled**: Server-side rendering for SEO optimization
- **RTL Support**: Right-to-left layout for Arabic content
- **TypeScript**: TypeScript support (non-strict mode)
- **Tailwind CSS**: Utility-first CSS framework
- **i18n**: Internationalization support (Arabic/English)
- **API Integration**: Backend API integration via composables

## Project Structure

```
public-website/
├── app.vue                 # Root app component
├── nuxt.config.ts         # Main Nuxt configuration
├── tailwind.config.js     # Tailwind CSS configuration
├── tsconfig.json          # TypeScript configuration
├── package.json           # Dependencies and scripts
├── env.example            # Environment variables template
│
├── assets/                # Processed assets (CSS, images, fonts)
│   └── css/
│       └── main.css       # Main stylesheet with Tailwind directives
│
├── public/                # Static assets (served as-is)
│   ├── favicon.ico
│   └── _robots.txt
│
├── components/            # Vue components
├── composables/           # Composable functions (useApi, useAuth)
├── layouts/               # Layout components
├── pages/                 # File-based routing
├── plugins/               # Nuxt plugins
├── locales/              # i18n translation files
└── utils/                # Utility functions
```

## Nuxt Configuration

The main configuration file is `nuxt.config.ts`. Key sections:

### Basic Configuration

```typescript
export default defineNuxtConfig({
  devtools: { enabled: true },
  ssr: true,  // Server-side rendering enabled
  compatibilityDate: '2025-10-17'
})
```

### Modules

Modules are configured in the `modules` array:

```typescript
modules: [
  '@nuxtjs/tailwindcss',    // Tailwind CSS integration
  '@nuxtjs/google-fonts'    // Google Fonts integration
]
```

**To add a new module:**

1. Install the package: `npm install @nuxtjs/module-name`
2. Add to `modules` array in `nuxt.config.ts`
3. Configure module-specific options if needed

### CSS Configuration

Global CSS files are imported via the `css` array:

```typescript
css: ['~/assets/css/main.css']
```

**To add a new CSS file:**

1. Place the file in `assets/css/` directory
2. Add to the `css` array: `css: ['~/assets/css/main.css', '~/assets/css/custom.css']`

### App Configuration

App-wide settings including HTML attributes and meta tags:

```typescript
app: {
  head: {
    htmlAttrs: {
      dir: 'rtl',      // Right-to-left direction
      lang: 'ar'       // Arabic language
    },
    meta: [
      { charset: 'utf-8' },
      { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      { name: 'format-detection', content: 'telephone=no' }
    ]
  }
}
```

**To modify head configuration:**

- Use `useHead()` composable in components for page-specific meta tags
- Modify `app.head` in `nuxt.config.ts` for global settings

### Router Configuration

```typescript
router: {
  options: {
    strict: false  // Allows trailing slashes
  }
}
```

### TypeScript Configuration

```typescript
typescript: {
  strict: false,      // Non-strict TypeScript mode
  typeCheck: false   // Disable type checking during build
}
```

### Development Server

```typescript
devServer: {
  port: 3000  // Development server port
}
```

### Vite Configuration

Vite-specific settings for build optimization:

```typescript
vite: {
  optimizeDeps: {
    exclude: ['@unhead/vue']  // Exclude problematic dependencies
  },
  resolve: {
    alias: {
      'node:sqlite': './node_modules/node-sqlite-polyfill.js'
    }
  }
}
```

### Nitro Configuration

Nitro (Nuxt's server engine) configuration:

```typescript
nitro: {
  experimental: {
    wasm: false,
    database: false
  },
  externals: {
    inline: ['node:sqlite', 'better-sqlite3', 'db0']
  }
}
```

## Environment Variables

Environment variables are configured in `.env` file (create from `env.example`).

### Required Variables

```bash
# Nuxt Configuration
NUXT_PUBLIC_SITE_URL=http://localhost:3000
NUXT_PUBLIC_API_BASE=http://localhost:8000/api

# API Configuration
API_SECRET=your-api-secret-key

# Development
NODE_ENV=development
```

### Variable Naming

- **`NUXT_PUBLIC_*`**: Exposed to client-side (accessible in browser)
- **No prefix**: Server-side only (accessible only in SSR context)

### Accessing Environment Variables

**In `nuxt.config.ts` (Runtime Config):**

```typescript
runtimeConfig: {
  apiSecret: process.env.API_SECRET,  // Server-side only
  public: {
    apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
    siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000'
  }
}
```

**In Components/Composables:**

```typescript
const config = useRuntimeConfig()
const apiBase = config.public.apiBase  // Public variable
const apiSecret = config.apiSecret     // Server-side only (undefined in browser)
```

**Direct Access (Not Recommended):**

```typescript
// Only works for NUXT_PUBLIC_* variables
const apiBase = process.env.NUXT_PUBLIC_API_BASE
```

### Setting Up Environment Variables

1. Copy `env.example` to `.env`:
   ```bash
   cp env.example .env
   ```

2. Update values in `.env` file

3. Restart development server after changes

## Asset Management

### Assets Directory (`assets/`)

The `assets/` directory contains files that are processed by Vite during build:

- **CSS files**: Processed and bundled
- **Images**: Optimized and hashed
- **Fonts**: Processed and optimized
- **Other files**: Transformed as needed

**Importing from assets:**

```vue
<template>
  <!-- Images -->
  <img :src="imageSrc" alt="Description" />
</template>

<script setup>
// Import image
import logo from '~/assets/images/logo.png'
const imageSrc = logo

// Import CSS (if not in main.css)
import '~/assets/css/custom.css'
</script>
```

### Public Directory (`public/`)

The `public/` directory contains static files served as-is:

- **Favicon**: `public/favicon.ico`
- **Robots.txt**: `public/_robots.txt` (underscore prefix required)
- **Other static files**: Images, documents, etc.

**Referencing public assets:**

```vue
<template>
  <!-- Direct path reference -->
  <img src="/favicon.ico" alt="Favicon" />
  <link rel="icon" href="/favicon.ico" />
</template>
```

**Key Differences:**

| Assets (`assets/`) | Public (`public/`) |
|-------------------|-------------------|
| Processed by Vite | Served as-is |
| Import required | Direct path reference |
| Optimized/hashed | Original files |
| Use for: CSS, processed images | Use for: favicon, robots.txt, static files |

### CSS Assets

Main CSS file: `assets/css/main.css`

**Structure:**

1. **Font Imports** (if using external fonts)
2. **Tailwind Directives**:
   ```css
   @tailwind base;
   @tailwind components;
   @tailwind utilities;
   ```
3. **CSS Variables** (custom properties)
4. **Custom Styles** (component classes, utilities)

**Adding Custom CSS:**

1. Add to `main.css` for global styles
2. Use `<style>` blocks in components for component-specific styles
3. Use Tailwind's `@apply` directive for utility-based classes

### Image Assets

**Best Practices:**

1. **Small images (< 10KB)**: Use `assets/` for optimization
2. **Large images**: Consider CDN or `public/` directory
3. **Dynamic images**: Load from API/CDN
4. **Favicon/Static**: Use `public/` directory

**Example:**

```vue
<template>
  <!-- Static image from assets -->
  <img :src="logo" alt="Logo" />
  
  <!-- Static image from public -->
  <img src="/images/hero.jpg" alt="Hero" />
  
  <!-- Dynamic image from API -->
  <img :src="course.thumbnail" :alt="course.title" />
</template>

<script setup>
import logo from '~/assets/images/logo.png'
</script>
```

### Font Assets

Fonts are configured via Google Fonts module:

```typescript
googleFonts: {
  families: {
    Cairo: [300, 400, 500, 600, 700, 800, 900]
  },
  display: 'swap',
  preload: true,
  download: true
}
```

**To add a new font:**

1. Add to `googleFonts.families` in `nuxt.config.ts`
2. Or import in `assets/css/main.css`:
   ```css
   @import url('https://fonts.googleapis.com/css2?family=FontName:wght@400;700&display=swap');
   ```

## Module Configuration

### Tailwind CSS Module

Configured in `nuxt.config.ts`:

```typescript
modules: ['@nuxtjs/tailwindcss']
```

Configuration file: `tailwind.config.js`

See `TAILWIND_SETUP.md` for detailed Tailwind configuration.

### Google Fonts Module

Configured in `nuxt.config.ts`:

```typescript
modules: ['@nuxtjs/google-fonts']

googleFonts: {
  families: {
    Cairo: [300, 400, 500, 600, 700, 800, 900]
  },
  display: 'swap',    // Font display strategy
  preload: true,       // Preload fonts
  download: true       # Download fonts locally
}
```

**Options:**

- `display`: Font loading strategy (`swap`, `block`, `fallback`, `optional`)
- `preload`: Preload fonts for faster rendering
- `download`: Download fonts locally instead of using CDN

## Runtime Configuration

Runtime configuration allows access to environment variables:

```typescript
runtimeConfig: {
  // Private keys (server-side only)
  apiSecret: process.env.API_SECRET,
  
  // Public keys (exposed to client-side)
  public: {
    apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
    siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000'
  }
}
```

**Usage in Composables:**

```typescript
// composables/useApi.ts
export const useApi = () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase
  
  // Use apiBase for API calls
}
```

## Development Setup

### Initial Setup

1. **Install Dependencies:**
   ```bash
   npm install
   ```

2. **Configure Environment:**
   ```bash
   cp env.example .env
   # Edit .env with your values
   ```

3. **Start Development Server:**
   ```bash
   npm run dev
   ```

4. **Access Application:**
   - Open `http://localhost:3000`

### Available Scripts

```json
{
  "dev": "nuxt dev",           // Start development server
  "build": "nuxt build",       // Build for production
  "generate": "nuxt generate",  // Generate static site
  "preview": "nuxt preview"    // Preview production build
}
```

### Development Workflow

1. **Making Changes:**
   - Edit files in `pages/`, `components/`, `layouts/`
   - Changes are hot-reloaded automatically
   - CSS changes in `assets/css/main.css` require page refresh

2. **Adding New Pages:**
   - Create `.vue` file in `pages/` directory
   - Routes are automatically generated based on file structure

3. **Adding Components:**
   - Create `.vue` file in `components/` directory
   - Auto-imported (no manual import needed)

4. **Adding Composables:**
   - Create `.ts` file in `composables/` directory
   - Export functions with `use` prefix
   - Auto-imported in components

## Common Configuration Tasks

### Adding a New Module

1. **Install the module:**
   ```bash
   npm install @nuxtjs/module-name
   ```

2. **Add to `nuxt.config.ts`:**
   ```typescript
   modules: [
     '@nuxtjs/tailwindcss',
     '@nuxtjs/google-fonts',
     '@nuxtjs/module-name'  // Add here
   ]
   ```

3. **Configure module options** (if needed):
   ```typescript
   moduleName: {
     // Module-specific options
   }
   ```

### Adding Global CSS

1. **Create CSS file in `assets/css/`:**
   ```css
   /* assets/css/custom.css */
   .custom-class {
     color: red;
   }
   ```

2. **Import in `nuxt.config.ts`:**
   ```typescript
   css: [
     '~/assets/css/main.css',
     '~/assets/css/custom.css'  // Add here
   ]
   ```

### Adding Environment Variables

1. **Add to `.env` file:**
   ```bash
   NUXT_PUBLIC_NEW_VAR=value
   ```

2. **Add to `nuxt.config.ts` (if needed in runtime config):**
   ```typescript
   runtimeConfig: {
     public: {
       newVar: process.env.NUXT_PUBLIC_NEW_VAR
     }
   }
   ```

3. **Use in components:**
   ```typescript
   const config = useRuntimeConfig()
   const newVar = config.public.newVar
   ```

### Adding Static Assets

1. **Place file in `public/` directory:**
   ```
   public/images/logo.png
   ```

2. **Reference in templates:**
   ```vue
   <img src="/images/logo.png" alt="Logo" />
   ```

### Changing Development Port

Edit `nuxt.config.ts`:

```typescript
devServer: {
  port: 3001  // Change port number
}
```

### Disabling SSR

Edit `nuxt.config.ts`:

```typescript
ssr: false  // Disable server-side rendering
```

**Note:** This affects SEO. Only disable if necessary (e.g., SPA mode).

## Troubleshooting

### Module Not Working

1. **Check installation:**
   ```bash
   npm list @nuxtjs/module-name
   ```

2. **Verify module in `nuxt.config.ts`:**
   - Ensure module is in `modules` array
   - Check for typos in module name

3. **Restart dev server:**
   ```bash
   # Stop server (Ctrl+C)
   npm run dev
   ```

### CSS Not Loading

1. **Check CSS import in `nuxt.config.ts`:**
   ```typescript
   css: ['~/assets/css/main.css']
   ```

2. **Verify file path:**
   - Use `~/` prefix for root-relative paths
   - Check file exists in `assets/css/`

3. **Clear `.nuxt` cache:**
   ```bash
   rm -rf .nuxt
   npm run dev
   ```

### Environment Variables Not Working

1. **Check variable naming:**
   - Public variables must start with `NUXT_PUBLIC_`
   - Server-only variables have no prefix

2. **Verify `.env` file exists:**
   ```bash
   ls -la .env
   ```

3. **Restart dev server** after changing `.env`

4. **Check runtime config:**
   - Ensure variable is in `runtimeConfig` if accessing via `useRuntimeConfig()`

### Build Errors

1. **Clear build cache:**
   ```bash
   rm -rf .nuxt .output node_modules/.cache
   npm install
   npm run build
   ```

2. **Check TypeScript errors:**
   ```bash
   npm run build
   # Review error messages
   ```

3. **Verify all dependencies installed:**
   ```bash
   npm install
   ```

### Port Already in Use

1. **Change port in `nuxt.config.ts`:**
   ```typescript
   devServer: {
     port: 3001
   }
   ```

2. **Or kill process using port:**
   ```bash
   # Windows
   netstat -ano | findstr :3000
   taskkill /PID <PID> /F
   
   # Linux/Mac
   lsof -ti:3000 | xargs kill
   ```

## Additional Resources

- [Nuxt 3 Documentation](https://nuxt.com/docs)
- [Nuxt Configuration Reference](https://nuxt.com/docs/api/configuration/nuxt-config)
- [Vite Asset Handling](https://vitejs.dev/guide/assets.html)
- [Tailwind Setup Guide](./TAILWIND_SETUP.md)

## Quick Reference

### File Paths

- **Config**: `nuxt.config.ts`
- **CSS**: `assets/css/main.css`
- **Public Assets**: `public/`
- **Components**: `components/`
- **Pages**: `pages/`
- **Composables**: `composables/`

### Common Commands

```bash
npm run dev      # Start development
npm run build    # Build for production
npm run preview  # Preview production build
```

### Important Notes

- Always use `NUXT_PUBLIC_` prefix for client-accessible environment variables
- Assets in `assets/` are processed; use `public/` for static files
- Restart dev server after changing `nuxt.config.ts` or `.env`
- Components and composables are auto-imported (no manual imports needed)

