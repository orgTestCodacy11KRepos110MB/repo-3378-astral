import { fetchStarsQuery } from '@/queries'
import { FetchDirection, FetchDirections } from '@/types'

self.addEventListener('message', async ({ data }) => {
  const cursor: string = data.cursor || null
  const direction: FetchDirection = data.direction || FetchDirections.DESC

  const result = await (
    await fetch('https://api.github.com/graphql', {
      method: 'POST',
      headers: {
        Authorization: `bearer ${data.token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        query: fetchStarsQuery(cursor, direction),
      }),
    })
  ).json()

  self.postMessage(result.data)
})

export default self