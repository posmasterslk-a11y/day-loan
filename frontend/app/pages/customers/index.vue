<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { User } from '~/types'

const UAvatar = resolveComponent('UAvatar')
const UButton = resolveComponent('UButton')
const UBadge = resolveComponent('UBadge')
const UDropdownMenu = resolveComponent('UDropdownMenu')
const UCheckbox = resolveComponent('UCheckbox')

const toast = useToast()
const table = useTemplateRef('table')
const router = useRouter()

const columnFilters = ref([{
  id: 'email',
  value: ''
}])
const columnVisibility = ref()
const rowSelection = ref({})

const isDetailsSlideoverOpen = ref(false)
const selectedCustomerDetails = ref<any>(null)
const isEditing = ref(false)
const editState = ref<any>({})

watch(isDetailsSlideoverOpen, (val) => {
  if (!val) isEditing.value = false
})

async function saveCustomerEdits() {
  try {
    const res = await useApi()(`${config.public.apiBase}/customers/${selectedCustomerDetails.value.db_id}`, {
      method: 'PUT',
      body: {
         full_name: editState.value.name,
         nic: editState.value.nic,
         email: editState.value.email,
         phone: editState.value.phone,
         whatsapp: editState.value.whatsapp,
         address: editState.value.address || editState.value.location,
         dob: editState.value.dob,
         gender: editState.value.gender,
         business_type: editState.value.business_type,
         monthly_income: editState.value.monthly_income,
         status: editState.value.status
      }
    })
    
    // Update local state
    Object.assign(selectedCustomerDetails.value, editState.value)
    
    toast.add({ title: 'Success', description: 'Customer details updated successfully', color: 'success' })
    isEditing.value = false
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to update customer', color: 'error' })
  }
}

const config = useRuntimeConfig()
const { data, status, refresh } = await useApiFetch<any[]>(`${config.public.apiBase}/customers`, {
  lazy: true
})

function getRowItems(row: Row<any>) {
  return [
    {
      type: 'label',
      label: 'Actions'
    },
    {
      label: 'Customer Analytics',
      icon: 'i-lucide-bar-chart-2',
      onSelect() {
         router.push(`/customers/${row.original.db_id}`)
      }
    },
    {
      label: 'Copy customer ID',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(row.original.id.toString())
        toast.add({
          title: 'Copied to clipboard',
          description: 'Customer ID copied to clipboard'
        })
      }
    },
    {
      type: 'separator'
    },
    {
      label: 'View customer details',
      icon: 'i-lucide-list',
      onSelect() {
        selectedCustomerDetails.value = row.original
        editState.value = { ...row.original }
        isDetailsSlideoverOpen.value = true
      }
    },
    {
      label: 'View customer payments',
      icon: 'i-lucide-wallet'
    },
    {
      type: 'separator'
    },
    {
      label: 'Delete customer',
      icon: 'i-lucide-trash',
      color: 'error',
      onSelect() {
        toast.add({
          title: 'Customer deleted',
          description: 'The customer has been deleted.'
        })
      }
    }
  ]
}

const columns: TableColumn<any>[] = [
  {
    id: 'select',
    header: ({ table }) =>
      h(UCheckbox, {
        'modelValue': table.getIsSomePageRowsSelected()
          ? 'indeterminate'
          : table.getIsAllPageRowsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
          table.toggleAllPageRowsSelected(!!value),
        'ariaLabel': 'Select all'
      }),
    cell: ({ row }) =>
      h(UCheckbox, {
        'modelValue': row.getIsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
        'ariaLabel': 'Select row'
      })
  },
  {
    accessorKey: 'id',
    header: 'ID'
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-3' }, [
        h(UAvatar, {
          ...row.original.avatar,
          size: 'lg'
        }),
        h('div', undefined, [
          h('p', { class: 'font-medium text-highlighted' }, row.original.name),
          h('p', { class: '' }, `@${row.original.name}`)
        ])
      ])
    }
  },
  {
    accessorKey: 'email',
    header: ({ column }) => {
      const isSorted = column.getIsSorted()

      return h(UButton, {
        color: 'neutral',
        variant: 'ghost',
        label: 'Email',
        icon: isSorted
          ? isSorted === 'asc'
            ? 'i-lucide-arrow-up-narrow-wide'
            : 'i-lucide-arrow-down-wide-narrow'
          : 'i-lucide-arrow-up-down',
        class: '-mx-2.5',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc')
      })
    }
  },
  {
    accessorKey: 'location',
    header: 'Location',
    cell: ({ row }) => row.original.location
  },
  {
    accessorKey: 'status',
    header: 'Status',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = {
        active: 'success' as const,
        inactive: 'error' as const,
        pending: 'warning' as const
      }[row.original.status] || 'neutral'

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        row.original.status
      )
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(
          UDropdownMenu,
          {
            content: {
              align: 'end'
            },
            items: getRowItems(row)
          },
          () =>
            h(UButton, {
              icon: 'i-lucide-ellipsis-vertical',
              color: 'neutral',
              variant: 'ghost',
              class: 'ml-auto'
            })
        )
      )
    }
  }
]

const statusFilter = ref('all')

watch(() => statusFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return

  const statusColumn = table.value.tableApi.getColumn('status')
  if (!statusColumn) return

  if (newVal === 'all') {
    statusColumn.setFilterValue(undefined)
  } else {
    statusColumn.setFilterValue(newVal)
  }
})

const searchQuery = ref('')

const filteredData = computed(() => {
  if (!data.value) return []
  if (!searchQuery.value) return data.value
  
  const q = searchQuery.value.toLowerCase()
  return data.value.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) ||
    (c.nic && c.nic.toLowerCase().includes(q)) ||
    (c.phone && c.phone.toLowerCase().includes(q)) ||
    (c.email && c.email.toLowerCase().includes(q))
  )
})

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})
</script>

<template>
  <UDashboardPanel id="customers">
    <template #header>
      <UDashboardNavbar title="Customers">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <CustomersAddModal @refresh="refresh" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="searchQuery"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Search by Name, NIC, or Phone..."
        />

        <div class="flex flex-wrap items-center gap-1.5">
          <CustomersDeleteModal :count="table?.tableApi?.getFilteredSelectedRowModel().rows.length">
            <UButton
              v-if="table?.tableApi?.getFilteredSelectedRowModel().rows.length"
              label="Delete"
              color="error"
              variant="subtle"
              icon="i-lucide-trash"
            >
              <template #trailing>
                <UKbd>
                  {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length }}
                </UKbd>
              </template>
            </UButton>
          </CustomersDeleteModal>

          <USelect
            v-model="statusFilter"
            :items="[
              { label: 'All', value: 'all' },
              { label: 'Active', value: 'active' },
              { label: 'Inactive', value: 'inactive' },
              { label: 'Pending', value: 'pending' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filter status"
            class="min-w-28"
          />
          <UDropdownMenu
            :items="
              table?.tableApi
                ?.getAllColumns()
                .filter((column: any) => column.getCanHide())
                .map((column: any) => ({
                  label: upperFirst(column.id),
                  type: 'checkbox' as const,
                  checked: column.getIsVisible(),
                  onUpdateChecked(checked: boolean) {
                    table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                  },
                  onSelect(e?: Event) {
                    e?.preventDefault()
                  }
                }))
            "
            :content="{ align: 'end' }"
          >
            <UButton
              label="Display"
              color="neutral"
              variant="outline"
              trailing-icon="i-lucide-settings-2"
            />
          </UDropdownMenu>
        </div>
      </div>

      <UTable
        ref="table"
        v-model:column-filters="columnFilters"
        v-model:column-visibility="columnVisibility"
        v-model:row-selection="rowSelection"
        v-model:pagination="pagination"
        :pagination-options="{
          getPaginationRowModel: getPaginationRowModel()
        }"
        class="shrink-0"
        :data="filteredData"
        :columns="columns"
        :loading="status === 'pending'"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          tr: {
            base: '',
            active: 'hover:bg-gray-50 dark:hover:bg-gray-800/50',
            selected: 'bg-gray-50 dark:bg-gray-800/50',
          },
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
        :row-class="(row) => row.original.is_7_days_arrears ? 'bg-red-50 dark:bg-red-900/20' : ''"
      />

      <div class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-auto">
        <div class="text-sm text-muted">
          {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
          {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} row(s) selected.
        </div>

        <div class="flex items-center gap-1.5">
          <UPagination
            :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
            :items-per-page="table?.tableApi?.getState().pagination.pageSize"
            :total="table?.tableApi?.getFilteredRowModel().rows.length"
            @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
          />
        </div>
      </div>
      
      <!-- Customer Details Slideover -->
      <USlideover v-model:open="isDetailsSlideoverOpen" title="Customer Details" description="Personal and contact information">
        <template #body v-if="selectedCustomerDetails">
           
           <div class="flex justify-end mb-4 border-b border-gray-100 pb-2">
              <UButton 
                 v-if="!isEditing" 
                 icon="i-lucide-edit" 
                 label="Edit Profile" 
                 color="primary" 
                 variant="subtle" 
                 size="sm" 
                 @click="isEditing = true; editState = { ...selectedCustomerDetails }" 
              />
              <div v-else class="flex gap-2">
                 <UButton label="Cancel" color="neutral" variant="ghost" size="sm" @click="isEditing = false" />
                 <UButton icon="i-lucide-save" label="Save Changes" color="success" size="sm" @click="saveCustomerEdits" />
              </div>
           </div>

           <div class="space-y-6" v-if="!isEditing">
              
              <div class="flex items-center gap-4 border-b border-gray-100 dark:border-gray-800 pb-6">
                 <UAvatar :src="selectedCustomerDetails.avatar?.src" size="2xl" />
                 <div>
                    <h3 class="text-xl font-bold">{{ selectedCustomerDetails.name }}</h3>
                    <p class="text-muted-foreground">{{ selectedCustomerDetails.email || 'No email provided' }}</p>
                    <UBadge :color="selectedCustomerDetails.status === 'active' ? 'success' : (selectedCustomerDetails.status === 'inactive' ? 'error' : 'warning')" class="mt-2 capitalize">{{ selectedCustomerDetails.status }}</UBadge>
                 </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                 <div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">NIC Number</p>
                    <p class="font-medium mt-1">{{ selectedCustomerDetails.nic || 'N/A' }}</p>
                 </div>
                 <div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Gender</p>
                    <p class="font-medium mt-1">{{ selectedCustomerDetails.gender || 'N/A' }}</p>
                 </div>
                 <div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date of Birth</p>
                    <p class="font-medium mt-1">{{ selectedCustomerDetails.dob || 'N/A' }}</p>
                 </div>
                 <div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Phone (Mobile)</p>
                    <p class="font-medium mt-1">{{ selectedCustomerDetails.phone || 'N/A' }}</p>
                 </div>
                 <div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">WhatsApp</p>
                    <p class="font-medium mt-1">{{ selectedCustomerDetails.whatsapp || 'N/A' }}</p>
                 </div>
                 <div class="col-span-2">
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Address</p>
                    <p class="font-medium mt-1">{{ selectedCustomerDetails.address || selectedCustomerDetails.location || 'N/A' }}</p>
                 </div>
              </div>

              <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
                 <h4 class="font-semibold mb-4">Business & Financial</h4>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                       <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Business Type</p>
                       <p class="font-medium mt-1">{{ selectedCustomerDetails.business_type || 'N/A' }}</p>
                    </div>
                    <div>
                       <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Monthly Income</p>
                       <p class="font-medium mt-1">{{ selectedCustomerDetails.monthly_income ? `Rs. ${selectedCustomerDetails.monthly_income}` : 'N/A' }}</p>
                    </div>
                 </div>
              </div>
           </div>

           <!-- Edit Mode Form -->
           <div v-else class="space-y-4">
              <UFormField label="Full Name">
                 <UInput v-model="editState.name" class="w-full" />
              </UFormField>
              <UFormField label="NIC Number">
                 <UInput v-model="editState.nic" class="w-full" />
              </UFormField>
              <div class="grid grid-cols-2 gap-4">
                 <UFormField label="Phone">
                    <UInput v-model="editState.phone" class="w-full" />
                 </UFormField>
                 <UFormField label="WhatsApp">
                    <UInput v-model="editState.whatsapp" class="w-full" />
                 </UFormField>
              </div>
              <UFormField label="Email Address">
                 <UInput v-model="editState.email" type="email" class="w-full" />
              </UFormField>
              <UFormField label="Home Address">
                 <UTextarea v-model="editState.address" class="w-full" />
              </UFormField>
              <div class="grid grid-cols-2 gap-4">
                 <UFormField label="Gender">
                    <USelect v-model="editState.gender" :items="[{label:'Male',value:'Male'},{label:'Female',value:'Female'}]" class="w-full" />
                 </UFormField>
                 <UFormField label="Date of Birth">
                    <UInput v-model="editState.dob" type="date" class="w-full" />
                 </UFormField>
              </div>
              <div class="grid grid-cols-2 gap-4">
                 <UFormField label="Business Type">
                    <UInput v-model="editState.business_type" class="w-full" />
                 </UFormField>
                 <UFormField label="Monthly Income">
                    <UInput v-model="editState.monthly_income" type="number" class="w-full" />
                 </UFormField>
              </div>
              <UFormField label="Status">
                 <USelect v-model="editState.status" :items="[{label:'Active',value:'active'},{label:'Inactive',value:'inactive'},{label:'Pending',value:'pending'}]" class="w-full" />
              </UFormField>
           </div>
        </template>
      </USlideover>

    </template>
  </UDashboardPanel>
</template>
