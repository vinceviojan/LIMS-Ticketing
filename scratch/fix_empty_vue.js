const fs = require('fs');
const path = require('path');

function walk(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(file => {
    file = path.join(dir, file);
    const stat = fs.statSync(file);
    if (stat && stat.isDirectory()) {
      results = results.concat(walk(file));
    } else if (file.endsWith('.vue')) {
      results.push({ path: file, size: stat.size });
    }
  });
  return results;
}

const srcDir = path.join(__dirname, '..', 'frontend', 'src');
const vueFiles = walk(srcDir);

vueFiles.forEach(({ path: filePath, size }) => {
  if (size === 0) {
    const fileName = path.basename(filePath, '.vue');
    let content = '';
    if (filePath.includes('layouts')) {
      content = `<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
</script>
`;
    } else if (filePath.includes('components')) {
      content = `<template>
  <div>
    <!-- ${fileName} -->
  </div>
</template>

<script setup>
</script>
`;
    } else {
      content = `<template>
  <q-page class="flex flex-center">
    <div class="text-h4">${fileName}</div>
  </q-page>
</template>

<script setup>
</script>
`;
    }
    fs.writeFileSync(filePath, content, 'utf8');
    console.log('Populated:', filePath);
  }
});

// Also ensure frontend/src/pages/index.vue exists
const indexPath = path.join(srcDir, 'pages', 'index.vue');
if (!fs.existsSync(indexPath)) {
  const indexContent = `<template>
  <q-page class="flex flex-center">
    <div class="text-h4 text-primary">LIMS Ticketing System</div>
  </q-page>
</template>

<script setup>
</script>
`;
  fs.writeFileSync(indexPath, indexContent, 'utf8');
  console.log('Created:', indexPath);
}
