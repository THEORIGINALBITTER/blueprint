import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import BitterButtonWithMenu from '@denisbitter/bitter-button-menu';

const menuItems = [
  { id: 'blueprint', label: 'Start', angle: 0, onClick: () => { window.location.href = './'; } },
  { id: 'modell', label: 'Modell', angle: -36, onClick: () => { window.location.href = './memo.html?topic=model'; } },
  { id: 'skalierung', label: 'Scale', angle: -72, onClick: () => { window.location.href = './memo.html?topic=skalierung'; } },
  { id: 'nische', label: 'Nische', angle: -108, onClick: () => { window.location.href = './memo.html?topic=nische'; } },
  { id: 'demo', label: 'Demo', angle: -144, onClick: () => { window.location.href = './memo.html?topic=demo'; } },
  { id: 'zenorbit', label: 'Editor', angle: -180, onClick: () => { window.location.href = './admin/'; } },
];

function OrbitMenu() {
  return <BitterButtonWithMenu
    logoSrc="./assets/zenorbit-logo.svg?v=3"
    logoAlt="ZenOrbit Menü"
    mainMenuItems={menuItems}
    accentColor="#e9e4d8"
    tooltipText="Menü öffnen"
    config={{
      visual: { radius: 120, button: { width: 64, height: 64 }, 
      colors: { primary: '#10100f', 
        background: '#e9e4d8', 
        backgroundDark: '#e9e4d8', 
        text: '#10100f', 
        border: 'rgba(16,16,15,.35)', borderHighlight: '#10100f', backdrop: 'rgba(0,0,0,0)' }, backdrop: { blur: '8px' },
        menuItem: { borderRadius: '50%', borderWidth: 1, fontWeight: 600, letterSpacing: '0px', textTransform: 'none' } },
      animation: { menuItem: { stiffness: 260, damping: 20, staggerDelay: 0.05 }, backdrop: { duration: 0.2 }, logo: { openRotation: 180, closeRotation: 0, stiffness: 100, damping: 50 } },
    }}
  />;
}

const root = document.getElementById('orbit-root');
if (root) createRoot(root).render(<BrowserRouter><OrbitMenu /></BrowserRouter>);
