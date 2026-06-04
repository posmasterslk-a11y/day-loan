<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const schema = z.object({
  full_name: z.string().min(2, 'Too short'),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  nic: z.string().min(10, 'NIC is too short').max(12, 'NIC is too long'),
  dob: z.string().optional(),
  gender: z.string().optional(),
  phone: z.string().min(10, 'Phone number must be at least 10 characters'),
  whatsapp: z.string().optional(),
  address: z.string().optional(),
  business_type: z.string().optional(),
  monthly_income: z.number().optional(),
  officer_id: z.number().optional()
})

const open = ref(false)
const fileInputs = ref({
  photo: null as File | null,
  nic_front: null as File | null,
  nic_back: null as File | null
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  full_name: '',
  email: '',
  nic: '',
  dob: '',
  gender: 'Male',
  phone: '',
  whatsapp: '',
  address: '',
  business_type: '',
  monthly_income: undefined,
  officer_id: undefined
})

const toast = useToast()
const config = useRuntimeConfig()
const { data: users } = await useApiFetch<any[]>(`${config.public.apiBase}/users`)

function handleFileChange(event: Event, type: 'photo' | 'nic_front' | 'nic_back') {
  const target = event.target as HTMLInputElement
  if (target.files?.length) {
    fileInputs.value[type] = target.files[0]
  }
}

watch(() => state.nic, (newNic) => {
  if (!newNic) return;
  const nic = newNic.trim();
  let year = 0;
  let dayList = 0;
  
  if (nic.length === 10 && /^[0-9]{9}[vVxX]$/.test(nic)) {
    year = 1900 + parseInt(nic.substring(0, 2));
    dayList = parseInt(nic.substring(2, 5));
  } else if (nic.length === 12 && /^[0-9]{12}$/.test(nic)) {
    year = parseInt(nic.substring(0, 4));
    dayList = parseInt(nic.substring(4, 7));
  } else {
    return;
  }

  let gender = 'Male';
  if (dayList > 500) {
    gender = 'Female';
    dayList -= 500;
  }
  
  if (dayList > 0 && dayList <= 366) {
    // Use a leap year (e.g., 2004) to correctly handle Feb 29 in NIC calculations
    const d = new Date(2004, 0);
    d.setDate(dayList);
    
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    state.dob = `${year}-${mm}-${dd}`;
    state.gender = gender;
  }
});

const emit = defineEmits(['refresh'])

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    const formData = new FormData()
    Object.entries(event.data).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        formData.append(key, value.toString())
      }
    })
    
    if (fileInputs.value.photo) formData.append('photo', fileInputs.value.photo)
    if (fileInputs.value.nic_front) formData.append('nic_front', fileInputs.value.nic_front)
    if (fileInputs.value.nic_back) formData.append('nic_back', fileInputs.value.nic_back)

    await $fetch(`${config.public.apiBase}/customers`, {
      method: 'POST',
      body: formData
    })

    toast.add({ title: 'Success', description: `New customer ${event.data.full_name} added`, color: 'success' })
    open.value = false
    // Trigger refresh of the parent table
    emit('refresh')
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to create customer', color: 'error' })
  }
}
</script>

<template>
  <UModal v-model:open="open" title="New customer" description="Register a new customer">
    <UButton label="New customer" icon="i-lucide-plus" />

    <template #body>
      <UForm
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Full Name" name="full_name">
          <UInput v-model="state.full_name" class="w-full" />
        </UFormField>
        <UFormField label="Email" name="email">
          <UInput v-model="state.email" type="email" class="w-full" />
        </UFormField>
        <UFormField label="NIC" name="nic">
          <UInput v-model="state.nic" class="w-full" />
        </UFormField>
        <UFormField label="Date of Birth" name="dob">
          <UInput v-model="state.dob" type="date" class="w-full" />
        </UFormField>
        <UFormField label="Gender" name="gender">
          <USelect v-model="state.gender" :items="[{label: 'Male', value: 'Male'}, {label: 'Female', value: 'Female'}]" class="w-full" />
        </UFormField>
        <UFormField label="Phone" name="phone">
          <UInput v-model="state.phone" class="w-full" />
        </UFormField>
        <UFormField label="WhatsApp" name="whatsapp">
          <UInput v-model="state.whatsapp" class="w-full" />
        </UFormField>
        <UFormField label="Address" name="address">
          <UTextarea v-model="state.address" class="w-full" />
        </UFormField>
        <UFormField label="Business Type" name="business_type">
          <UInput v-model="state.business_type" class="w-full" />
        </UFormField>
        <UFormField label="Monthly Income" name="monthly_income">
          <UInput v-model="state.monthly_income" type="number" class="w-full" />
        </UFormField>
        <UFormField label="Assign Recovery Officer" name="officer_id">
          <USelect v-model="state.officer_id" :items="users?.map(u => ({ label: u.name, value: u.id }))" class="w-full" placeholder="Select an officer..." />
        </UFormField>

        <UFormField label="Photo" name="photo">
          <input type="file" accept="image/*" @change="e => handleFileChange(e, 'photo')" />
        </UFormField>
        <UFormField label="NIC Front" name="nic_front">
          <input type="file" accept="image/*" @change="e => handleFileChange(e, 'nic_front')" />
        </UFormField>
        <UFormField label="NIC Back" name="nic_back">
          <input type="file" accept="image/*" @change="e => handleFileChange(e, 'nic_back')" />
        </UFormField>

        <div class="flex justify-end gap-2 mt-4">
          <UButton
            label="Cancel"
            color="neutral"
            variant="subtle"
            @click="open = false"
          />
          <UButton
            label="Create"
            color="primary"
            variant="solid"
            type="submit"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
