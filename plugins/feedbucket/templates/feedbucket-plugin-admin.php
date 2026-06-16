<div class="wrap">
    <script type="text/javascript">
        window.feedbucketData = '<?php echo wp_json_encode($data); ?>';
        window.feedbucketRoles = '<?php echo wp_json_encode($roles); ?>';
        window.feedbucketNonce = '<?php echo wp_create_nonce('feedbucket_save_settings'); ?>';
    </script>
    <img src="<?php echo esc_url($logo); ?>" alt="Feedbucket logo" class="h-10 mt-2 mb-4">
    <div id="feedbucket">

        <div v-if="showSuccess" class="rounded-lg bg-green-50 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="!m-0 text-sm font-medium text-green-800">Settings updated</h3>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="space-y-8 sm:space-y-5">
                <div>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 my-0">Feedbucket Settings</h3>
                        <p class="!mt-1 max-w-2xl text-sm text-gray-500">Display Feedbucket on your website to collect feedback with screenshot and recordings.</p>
                    </div>

                    <div class="mt-6 sm:mt-5 sm:space-y-5 sm:border-t sm:border-0 sm:border-solid sm:border-gray-200">
                        <div class="sm:grid sm:grid-cols-2 sm:gap-12 sm:items-start sm:border-t sm:border-gray-200 sm:pt-5">
                            <div class="sm:col-span-1">
                                <label for="key" class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">Project key</label>
                                <div class="!mt-1 text-sm text-gray-400">
                                    Copy the project key from Feedbucket in here.<br>
                                </div>
                                <div class="mt-1">
                                    <button type="button" @click="showProjectKeyHelp = !showProjectKeyHelp" class="text-sm cursor-pointer text-indigo-600 bg-transparent border-none p-0">Where do I find my project key?</button>
                                    <div v-if="showProjectKeyHelp" class="mt-1 bg-gray-100 border border-gray-200 rounded-md p-2">
                                        <ol class="list-decimal list-outside text-sm text-gray-400">
                                            <li>Go to <a href="https://dashboard.feedbucket.app?utm_source=wordpress&utm_medium=settings" class="text-indigo-600 underline underline-offset-4 hover:text-indigo-800">feedbucket.app</a> and log in, or create a free 14 day trial.</li>
                                            <li>Go to your project and click on the tab <span class="font-mono text-xs bg-indigo-50 text-indigo-600 rounded-md px-1 py-0.5">Installation</span>.</li>
                                            <li>Click on <span class="font-mono text-xs bg-indigo-50 text-indigo-600 rounded-md px-1 py-0.5">Plugins &amp; Tools</span> and then <span class="font-mono text-xs bg-indigo-50 text-indigo-600 rounded-md px-1 py-0.5">WordPress</span>.</li>
                                            <li>Follow the instruction and you will find your project key on step 4.</li>
                                            <li>Copy the project key and paste it in the field above.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-1 sm:mt-0 sm:col-span-1">
                                <input v-model="key" type="text" id="key" class="!px-3 !py-1 w-56">
                            </div>
                        </div>
                        <div class="sm:grid sm:grid-cols-2 sm:gap-12 sm:items-start sm:border-t sm:border-0 sm:border-solid sm:border-gray-200 sm:pt-5">
                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">Enable Feedbucket</label>
                                <p class="!mt-1 text-sm text-gray-400">Easily activate Feedbucket both for the front end and for the admin area.</p>
                            </div>
                            <div class="mt-1 sm:mt-0 sm:col-span-1">
                                <div class="flex items-center">
                                    <input v-model="enable" type="checkbox" id="enable" class="!m-0" />
                                    <label for="enable" class="ml-2 font-medium text-gray-700 text-sm">Enable Feedbucket</label>
                                </div>
                                <div class="mt-4 flex items-center">
                                    <input v-model="enableAdmin" type="checkbox" id="enable-admin" class="!m-0" />
                                    <label for="enable-admin" class="ml-2 font-medium text-gray-700 text-sm">Enable Feedbucket in admin</label>
                                </div>
                            </div>
                        </div>
                        <div class="sm:grid sm:grid-cols-2 sm:gap-12 sm:items-start sm:border-t sm:border-0 sm:border-solid sm:border-gray-200 sm:pt-5">
                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">Visibility</label>
                                <p class="!mt-1 text-sm text-gray-400">Determine who will see Feedbucket on the website.</p>
                            </div>
                            <div class="mt-1 sm:mt-0 sm:col-span-1">
                                <div class="flex items-center">
                                    <input type="radio" v-model="visibility" value="all" id="visibility-all" class="!m-0" />
                                    <label for="visibility-all" class="ml-2 font-medium text-gray-700 text-sm">All users</label>
                                </div>
                                <div class="mt-4 flex items-center">
                                    <input type="radio" v-model="visibility" value="auth" id="visibility-auth" class="!m-0" />
                                    <label for="visibility-auth" class="ml-2 font-medium text-gray-700 text-sm">Only authenticated users</label>
                                </div>
                                <div class="mt-4 flex items-center">
                                    <input type="radio" v-model="visibility" value="role" id="visibility-role" class="!m-0" />
                                    <label for="visibility-role" class="ml-2 font-medium text-gray-700 text-sm">Only users with roles</label>
                                
                                </div>
                                <div v-if="visibility === 'role'" class="ml-8 mt-4">
                                    <div class="space-y-2">
                                        <div v-for="role in availableRoles" :key="role.slug" class="flex items-center">
                                            <input v-model="roles" type="checkbox" :id="`role${role.name}`" :value="role.slug" class="!m-0" />
                                            <label :for="`role${role.name}`" class="ml-2 font-medium text-gray-700 text-sm">{{ role.name }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sm:grid sm:grid-cols-2 sm:gap-12 sm:items-start sm:border-t sm:border-0 sm:border-solid sm:border-gray-200 sm:pt-5">
                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">Automatically set reporter</label>
                                <p class="!mt-1 text-sm text-gray-400">
                                    If the user is logged in to wordpress; the users name and email will be used for Feedbucket.
                                </p>
                            </div>
                            <div class="mt-1 sm:mt-0 sm:col-span-1">
                                <div class="flex items-center">
                                    <input v-model="setReporter" type="checkbox" id="set-reporter" class="!m-0" />
                                    <label for="set-reporter" class="ml-2 font-medium text-gray-700 text-sm">Enable</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-5 sm:border-t sm:border-0 sm:border-solid sm:border-gray-200">
                    <div class="flex justify-end items-center">
                        <div v-if="showSuccess" class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-2">
                                <h3 class="!m-0 text-sm font-medium text-green-800">Settings updated</h3>
                            </div>
                        </div>
                        <div class="ml-6">
                            <button type="submit" @click.prevent="submit" :disabled="submitting" :class="{'opacity-50 cursor-not-allowed': submitting, 'cursor-pointer hover:bg-indigo-700': !submitting}" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                key: JSON.parse(window.feedbucketData).key || "",
                enable: JSON.parse(window.feedbucketData).enable,
                enableAdmin: JSON.parse(window.feedbucketData).enableAdmin || false,
                visibility: JSON.parse(window.feedbucketData).visibility || 'all',
                roles: JSON.parse(window.feedbucketData).roles || [],
                setReporter: JSON.parse(window.feedbucketData).setReporter,
                availableRoles: JSON.parse(window.feedbucketRoles),
                showProjectKeyHelp: false,
                showSuccess: false,
                submitting: false
            }
        },

        methods: {
            submit() {
                this.submitting = true

                const data = {
                    action: 'feedbucket_save_options',
                    key: this.key,
                    enable: this.enable,
                    enableAdmin: this.enableAdmin,
                    visibility: this.visibility,
                    roles: this.roles,
                    setReporter: this.setReporter,
                    _ajax_nonce: window.feedbucketNonce,
                }

                jQuery.post(ajaxurl, data, response => {
                    this.showSuccess = true
                    this.submitting = false
                    setTimeout(() => {
                        this.showSuccess = false
                    }, 3000);
                })
            }
        }

    }).mount('#feedbucket')
</script>