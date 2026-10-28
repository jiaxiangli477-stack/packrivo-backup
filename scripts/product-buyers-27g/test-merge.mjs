import test from 'node:test';
import assert from 'node:assert/strict';
import { mergeKeepingProduction } from './merge-i18n.mjs';

test('production value wins same-bucket conflicts', () => {
  const base={title:{English:'Production'},block:{},text:{},attr:{}};
  const result=mergeKeepingProduction(base,{title:{English:'Reviewed'},block:{},text:{},attr:{}});
  assert.equal(result.merged.title.English,'Production');
  assert.equal(result.conflicts.length,1);
  assert.equal(base.title.English,'Production');
});
test('cross-bucket production value also wins and supplies missing target bucket', () => {
  const base={title:{},block:{English:'Production'},text:{},attr:{}};
  const result=mergeKeepingProduction(base,{title:{English:'Reviewed'},block:{},text:{English:'Reviewed'},attr:{}});
  assert.equal(result.merged.title.English,'Production');
  assert.equal(result.merged.text.English,'Production');
  assert.equal(result.merged.block.English,'Production');
  assert.equal(result.conflicts.length,1);
});
test('new keys and placeholders are copied unchanged', () => {
  const base={title:{},block:{},text:{},attr:{}};
  const result=mergeKeepingProduction(base,{title:{},block:{},text:{'{N} structures':'{N} Formen'},attr:{}});
  assert.equal(result.merged.text['{N} structures'],'{N} Formen');
  assert.equal(result.addedBucketKeys,1);
  assert.equal(result.conflicts.length,0);
  assert.deepEqual(base,{title:{},block:{},text:{},attr:{}});
});
