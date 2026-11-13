/*
 * This file is part of the TYPO3 extension "be_environment_indicator".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

/**
 * ES module for TYPO3 12 and later.
 *
 * Moves the environment indicator from the toolbar (right) next to the
 * site name in the top bar (left). Runs again after the core refreshed
 * the top bar ("t3-topbar-update" on the scaffold header).
 */
const movedClass = 'be-environment-indicator-moved';

function moveIndicator() {
  const indicator = document.querySelector('.toolbar-item-be-environment-indicator .be-environment-indicator');
  const site = document.querySelector('.scaffold-header .topbar-header-site');
  if (indicator === null || site === null) {
    return;
  }
  site.querySelectorAll('.' + movedClass).forEach((element) => element.remove());
  indicator.classList.add(movedClass);
  site.appendChild(indicator);
}

moveIndicator();

document.querySelector('.t3js-scaffold-header')?.addEventListener('t3-topbar-update', moveIndicator);
