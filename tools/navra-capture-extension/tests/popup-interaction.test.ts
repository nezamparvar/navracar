import fs from 'fs';
import path from 'path';

const popupSource = fs.readFileSync(
  path.join(__dirname, '../src/popup/popup.js'),
  'utf8',
);

function popupDom(): void {
  document.body.innerHTML = `
    <div id="auth-state"></div>
    <div id="authenticated-state"></div>
    <div id="listing-detected-state"></div>
    <div id="unsupported-page-state"></div>
    <div id="settings-panel" style="display:none"></div>
    <div id="batch-capture-state"></div>
    <button id="connect-btn"></button>
    <input id="pairing-code">
    <div id="auth-error"></div>
    <button id="send-btn"></button>
    <button id="preview-btn"></button>
    <button id="settings-btn"></button>
    <button id="disconnect-btn"></button>
    <button id="back-btn"></button>
    <button id="clear-history-btn"></button>
    <button id="batch-btn"></button>
    <button id="batch-back-btn"></button>
    <button id="batch-cancel-btn"></button>
    <button id="batch-send-btn"></button>
  `;
}

function loadPopup(sendMessage = jest.fn()): any {
  const chrome = {
    runtime: { sendMessage, lastError: null },
    tabs: { query: jest.fn(), sendMessage: jest.fn() },
    storage: { local: { set: jest.fn() } },
  };

  return new Function(
    'chrome',
    'alert',
    'confirm',
    `${popupSource}\nreturn {
      setupAuthenticatedListeners,
      setCurrentCapture(value) { currentCapture = value; }
    };`,
  )(chrome, jest.fn(), jest.fn(() => false));
}

describe('popup interaction resilience', () => {
  beforeEach(() => {
    delete document.documentElement.dataset.navraPopupBound;
    popupDom();
  });

  it('keeps actions working when a button element is recreated', () => {
    const popup = loadPopup();
    popup.setupAuthenticatedListeners();

    const oldButton = document.getElementById('settings-btn')!;
    const replacement = oldButton.cloneNode(true) as HTMLButtonElement;
    oldButton.replaceWith(replacement);
    replacement.click();

    expect(document.getElementById('settings-panel')!.style.display).toBe('block');
  });

  it('allows only one send operation while the runtime response is pending', () => {
    const sendMessage = jest.fn();
    const popup = loadPopup(sendMessage);
    popup.setCurrentCapture({ vehicle: { title: 'Mazda 3' }, images: [] });
    popup.setupAuthenticatedListeners();

    document.getElementById('send-btn')!.click();
    document.getElementById('send-btn')!.click();

    expect(sendMessage).toHaveBeenCalledTimes(1);
  });
});
