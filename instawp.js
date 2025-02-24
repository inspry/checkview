/**
 * Update the CheckView plugin on InstaWP sites.
 * 
 * @link https://documenter.getpostman.com/view/21495096/2s8YzUyhUf
 */
(async () => {
  const API_KEY = process.env.INSTAWP_API_KEY

  if (!API_KEY) {
    throw new Error('InstaWP API key not found.')
  }

  const headers = new Headers()
  headers.append('Accept', 'application/json')
  headers.append('Content-Type', 'application/json')
  headers.append('Authorization', `Bearer ${API_KEY}`)

  const base = 'https://app.instawp.io/api/v2/'

  const sitesEndpoint = new URL('sites', base)
  sitesEndpoint.searchParams.append('per_page', 999)
  sitesEndpoint.searchParams.append('tags', 6042) // depdev tag id

  try {
    const result = await fetch(sitesEndpoint, { headers })

    if (!result.ok) {
      throw new Error('Failed to fetch sites.')
    }

    const { data: sites } = await result.json()
    const failedSites = []

    const updatePromises = sites.map(async (site) => {
      try {
        const commandEndpoint = new URL(`sites/${site.id}/execute-command`, base)
        const body = JSON.stringify({ command_id: 1958 }) // Update CheckView plugin command id
        const updateResult = await fetch(commandEndpoint, { method: 'POST', headers, body })

        if (!updateResult.ok) {
          throw new Error(`Failed to execute command for site: ${site.name} (${site.id})`)
        }

        const responseData = await updateResult.json()

        if (!responseData.success) {
          throw new Error(`Command execution error for site: ${site.name} (${site.id}): ${responseData.message}`)
        }
      } catch (error) {
        console.log(`Error with site: ${site.name} (${site.id})`)
        failedSites.push(site.id)
      }
    })

    await Promise.all(updatePromises)

    if (failedSites.length) {
      console.log('Failed site executions:', failedSites)
    } else {
      console.log('All sites updated successfully. Get to testin!')
    }
  } catch (error) {
    console.log(error)
  }
})()
