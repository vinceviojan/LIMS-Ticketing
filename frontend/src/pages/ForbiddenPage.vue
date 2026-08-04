<template>
  <q-page class="flex flex-center column">
    <div class="text-h1 text-red">403</div>
    <div class="text-h5 q-mt-md">Access Forbidden</div>
    <q-btn
      class="q-mt-lg"
      color="primary"
      label="Go Back"
      @click="$router.back()"
    />
  </q-page>
</template>