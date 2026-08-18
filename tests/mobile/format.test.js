import test from 'node:test';
import assert from 'node:assert/strict';
import { bidiLtr, formatMoney, normalizePersianDigits } from '../../mobile/js/format.js';

test('formats large AED and toman values with Persian grouping', () => {
  assert.equal(formatMoney(345000, 'AED'), '۳۴۵٬۰۰۰ درهم');
  assert.equal(formatMoney(7635000000, 'IRR'), '۷٬۶۳۵٬۰۰۰٬۰۰۰ تومان');
});

test('normalizes Persian and Arabic digits for API numeric inputs', () => {
  assert.equal(normalizePersianDigits('۱۲٬۳۴۵٫۶٧'), '12345.67');
});

test('isolates mixed-direction vehicle identifiers without changing content', () => {
  assert.equal(bidiLtr('WBAJU7101M9E12345'), '\u2066WBAJU7101M9E12345\u2069');
});
