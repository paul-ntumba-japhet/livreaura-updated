<script setup lang="ts">
definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

await navigateTo('/administration/verification', { replace: true })
</script>
