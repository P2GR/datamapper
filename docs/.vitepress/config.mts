import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'DataMapper ORM 2.0',
  description: 'Modern Active Record ORM for CodeIgniter 3.x with fluent query builder, eager loading, and advanced features',
  
  // Base URL if deploying to GitHub Pages
  base: '/datamapper/',
  
  // Clean URLs (remove .html extension)
  cleanUrls: true,
  
  // Last updated timestamp
  lastUpdated: true,
  
  // Markdown configuration
  markdown: {
    theme: {
      light: 'github-light',
      dark: 'github-dark'
    },
    lineNumbers: true
  },
  
  // Head tags
  head: [
    ['link', { rel: 'icon', href: '/datamapper/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#3eaf7c' }],
    ['meta', { name: 'og:type', content: 'website' }],
    ['meta', { name: 'og:locale', content: 'en' }],
    ['meta', { name: 'og:site_name', content: 'DataMapper ORM' }],
  ],
  
  themeConfig: {
    // Logo
    logo: '/logo.svg',
    
    // Site title
    siteTitle: 'DataMapper ORM',
    
    // Navigation bar
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/guide/getting-started/introduction' },
      { 
        text: 'DataMapper 2.0',
        items: [
          { text: 'Fluent Query Builder', link: '/guide/datamapper-2/fluent' },
          { text: 'Eager Loading', link: '/guide/datamapper-2/eager-loading' },
          { text: 'Collections', link: '/guide/datamapper-2/collections' },
          { text: 'Query Caching', link: '/guide/datamapper-2/caching' },
          { text: 'Soft Deletes', link: '/guide/datamapper-2/soft-deletes' },
          { text: 'Timestamps', link: '/guide/datamapper-2/timestamps' },
        ]
      },
      { text: 'API Reference', link: '/reference/quick-reference' },
      { text: 'Examples', link: '/examples/' },
      { 
        text: 'v2.0.0',
        items: [
          { text: 'Changelog', link: '/help/changelog' },
          { text: 'Contributing', link: '/help/contributing' },
        ]
      }
    ],
    
    // Sidebar navigation
    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          collapsed: false,
          items: [
            { text: 'Introduction', link: '/guide/getting-started/introduction' },
            { text: 'Requirements', link: '/guide/getting-started/requirements' },
            { text: 'Installation', link: '/guide/getting-started/installation' },
            { text: 'Quick Start', link: '/guide/getting-started/quickstart' },
            { text: 'Configuration', link: '/guide/getting-started/configuration' },
            { text: 'Database Setup', link: '/guide/getting-started/database' },
            { text: 'Using in Controllers', link: '/guide/getting-started/controllers' },
            { text: 'Upgrading', link: '/guide/getting-started/upgrading' },
          ]
        },
        {
          text: 'Models & CRUD',
          collapsed: false,
          items: [
            { text: 'Overview', link: '/guide/models/' },
            { text: 'Creating Models', link: '/guide/models/creating' },
            { text: 'Get Methods', link: '/guide/models/get' },
            { text: 'Advanced Get', link: '/guide/models/get-advanced' },
            { text: 'Get Iterated', link: '/guide/models/get-iterated' },
            { text: 'Save', link: '/guide/models/save' },
            { text: 'Update', link: '/guide/models/update' },
            { text: 'Delete', link: '/guide/models/delete' },
            { text: 'Fields & Properties', link: '/guide/models/fields' },
            { text: 'From Array', link: '/guide/models/from-array' },
            { text: 'To Array', link: '/guide/models/to-array' },
            { text: 'To JSON', link: '/guide/models/to-json' },
            { text: 'Clone', link: '/guide/models/clone' },
            { text: 'Refresh', link: '/guide/models/refresh' },
          ]
        },
        {
          text: 'Relationships',
          collapsed: false,
          items: [
            { text: 'Overview', link: '/guide/relationships/' },
            { text: 'Relationship Types', link: '/guide/relationships/types' },
            { text: 'Accessing Relations', link: '/guide/relationships/accessing' },
            { text: 'Setting Relations', link: '/guide/relationships/setting' },
            { text: 'Saving Relations', link: '/guide/relationships/saving' },
            { text: 'Deleting Relations', link: '/guide/relationships/deleting' },
            { text: 'Advanced Usage', link: '/guide/relationships/advanced' },
          ]
        },
        {
          text: 'DataMapper 2.0',
          collapsed: false,
          items: [
            { text: "What's New", link: '/guide/datamapper-2/' },
            { text: 'Fluent Query Builder', link: '/guide/datamapper-2/fluent' },
            { text: 'Eager Loading', link: '/guide/datamapper-2/eager-loading' },
            { text: 'Collections', link: '/guide/datamapper-2/collections' },
            { text: 'Query Caching', link: '/guide/datamapper-2/caching' },
            { text: 'Soft Deletes', link: '/guide/datamapper-2/soft-deletes' },
            { text: 'Timestamps', link: '/guide/datamapper-2/timestamps' },
            { text: 'Attribute Casting', link: '/guide/datamapper-2/casting' },
            { text: 'Streaming Results', link: '/guide/datamapper-2/streaming' },
            { text: 'Advanced Queries', link: '/guide/datamapper-2/advanced-query-building' },
          ]
        },
        {
          text: 'Advanced Topics',
          collapsed: true,
          items: [
            { text: 'Advanced Usage', link: '/guide/advanced/usage' },
            { text: 'Subqueries', link: '/guide/advanced/subqueries' },
            { text: 'Joins', link: '/guide/advanced/joins' },
            { text: 'Transactions', link: '/guide/advanced/transactions' },
            { text: 'Validation', link: '/guide/advanced/validation' },
            { text: 'Production Cache', link: '/guide/advanced/production-cache' },
            { text: 'Localization', link: '/guide/advanced/localization' },
            { text: 'Table Prefix', link: '/guide/advanced/table-prefix' },
          ]
        },
        {
          text: 'Extensions',
          collapsed: true,
          items: [
            { text: 'Available Extensions', link: '/guide/extensions/' },
            { text: 'Writing Extensions', link: '/guide/extensions/writing' },
          ]
        },
      ],
      
      '/reference/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Quick Reference', link: '/reference/quick-reference' },
            { text: 'All Functions', link: '/reference/functions' },
            { text: 'Utility Functions', link: '/reference/utility' },
            { text: 'Reserved Names', link: '/reference/reserved-names' },
            { text: 'Glossary', link: '/reference/glossary' },
          ]
        }
      ],
      
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Code Examples', link: '/examples/' },
            { text: 'Blog System', link: '/examples/blog' },
            { text: 'E-commerce', link: '/examples/ecommerce' },
            { text: 'User Management', link: '/examples/users' },
          ]
        }
      ],
      
      '/help/': [
        {
          text: 'Help & Support',
          items: [
            { text: 'Troubleshooting', link: '/help/troubleshooting' },
            { text: 'FAQ', link: '/help/faq' },
            { text: 'Changelog', link: '/help/changelog' },
            { text: 'Roadmap', link: '/help/roadmap' },
            { text: 'Contributing', link: '/help/contributing' },
            { text: 'License', link: '/help/license' },
          ]
        }
      ],
    },
    
    // Social links
    socialLinks: [
      { icon: 'github', link: 'https://github.com/P2GR/datamapper' }
    ],
    
    // Footer
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2025-present DataMapper ORM'
    },
    
    // Edit link
    editLink: {
      pattern: 'https://github.com/P2GR/datamapper/edit/datamapper2/docs/:path',
      text: 'Edit this page on GitHub'
    },
    
    // Search (local)
    search: {
      provider: 'local',
      options: {
        detailedView: true
      }
    },
    
    // Outline (Table of Contents)
    outline: {
      level: [2, 3],
      label: 'On this page'
    },
    
    // Previous/Next links
    docFooter: {
      prev: 'Previous',
      next: 'Next'
    },
    
    // Dark mode toggle
    darkModeSwitchLabel: 'Appearance',
    
    // Return to top
    returnToTopLabel: 'Return to top',
    
    // Language toggle (for future i18n)
    langMenuLabel: 'Change language',
    
    // External link icon
    externalLinkIcon: true,
  },
  
  // Sitemap
  sitemap: {
    hostname: 'https://p2gr.github.io/datamapper'
  }
})
