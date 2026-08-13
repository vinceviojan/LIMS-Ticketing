import { api } from '../boot/axios'

export const fetchDivisions = async () => {
  try {
    const response = await api.get('/divisions')
    // Exclude the 'LIMS' section from each division
    const divisions = response.data.map(division => {
      return {
        ...division,
        sections: division.sections.filter(section => section.name !== 'LIMS' && section.code !== 'LIMS')
      }
    })
    return divisions
  } catch (error) {
    console.error('Error fetching divisions:', error)
    return []
  }
}

