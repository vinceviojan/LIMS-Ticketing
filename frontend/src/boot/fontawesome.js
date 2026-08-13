import { boot } from 'quasar/wrappers'
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import { faUser, faHouse, faGear, faMagnifyingGlass } from '@fortawesome/free-solid-svg-icons'

import { faGithub } from '@fortawesome/free-brands-svg-icons'

library.add(faUser, faHouse, faGear, faMagnifyingGlass, faGithub)

export default boot(({ app }) => {
  app.component('font-awesome-icon', FontAwesomeIcon)
})
