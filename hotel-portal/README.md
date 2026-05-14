# Hotel Management Web Portal

A modern hotel booking web portal built with Next.js, React, TypeScript, and Tailwind CSS.

## Features

- 🎨 Modern, responsive design with dark mode support
- 🔍 Hotel search functionality with date picker, guest selection, and promo codes
- 📍 Featured destinations showcase
- 🎯 Clean component-based architecture
- ⚡ Fast performance with Next.js
- 📱 Fully responsive mobile-first design

## Getting Started

### Prerequisites

- Node.js 18+ installed
- npm or yarn package manager

### Installation

1. Navigate to the project directory:
```bash
cd hotel-portal
```

2. Install dependencies:
```bash
npm install
```

3. Run the development server:
```bash
npm run dev
```

4. Open [http://localhost:3000](http://localhost:3000) in your browser to see the result.

## Project Structure

```
hotel-portal/
├── app/
│   ├── globals.css          # Global styles and Tailwind imports
│   ├── layout.tsx           # Root layout component
│   └── page.tsx             # Home page
├── components/
│   ├── Header.tsx           # Navigation header
│   ├── HeroSection.tsx      # Hero section with search form
│   ├── FeaturedDestinations.tsx  # Featured destinations grid
│   └── Footer.tsx           # Footer component
├── public/
│   └── Hotel Booking-rafiki.png  # Hero section background image
├── tailwind.config.js       # Tailwind CSS configuration
├── tsconfig.json            # TypeScript configuration
└── next.config.js           # Next.js configuration
```

## Image Setup

The hero section uses a local background image. To set it up:

1. Copy your `Hotel Booking-rafiki.png` file from your Downloads folder
2. Create a `public` folder in the `hotel-portal` directory (if it doesn't exist)
3. Place the image file in the `public` folder with the exact name: `Hotel Booking-rafiki.png`

The image will be accessible at `/Hotel Booking-rafiki.png` in your Next.js application.

## Customization

### Colors

The color scheme can be customized in `tailwind.config.js`:
- Primary: `#13a4ec`
- Background Light: `#f6f7f8`
- Background Dark: `#101c22`

### Fonts

The project uses "Plus Jakarta Sans" font family, which is imported in `app/globals.css`.

## Build for Production

```bash
npm run build
npm start
```

## Technologies Used

- **Next.js 14** - React framework
- **TypeScript** - Type safety
- **Tailwind CSS** - Utility-first CSS framework
- **Material Symbols** - Icon library

## License

This project is private and proprietary.
