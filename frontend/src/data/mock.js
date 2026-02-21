const styles = [
	{ id: 'style-1', name: '清透' },
	{ id: 'style-2', name: '电影感' },
	{ id: 'style-3', name: '复古' },
	{ id: 'style-4', name: '氛围' }
]

const banners = [
	{
		id: 'banner-1',
		url: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1200&q=80'
	},
	{
		id: 'banner-2',
		url: 'https://images.unsplash.com/photo-1500336624523-d727130c3328?auto=format&fit=crop&w=1200&q=80'
	},
	{
		id: 'banner-3',
		url: 'https://images.unsplash.com/photo-1524502397800-2eeaad7c3fe5?auto=format&fit=crop&w=1200&q=80'
	}
]

const templates = [
	{
		id: 'tpl-1',
		styleId: 'style-1',
		title: '奶油系头像',
		desc: '柔光、通透、干净肤感',
		coverUrl: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=600&q=80',
		tags: ['清透', '柔光'],
		subTemplates: [
			{
				id: 'sub-1-1',
				title: '柔光室内',
				thumbUrl: 'https://images.unsplash.com/photo-1500336624523-d727130c3328?auto=format&fit=crop&w=400&q=80'
			},
			{
				id: 'sub-1-2',
				title: '清晨窗边',
				thumbUrl: 'https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?auto=format&fit=crop&w=400&q=80'
			}
		]
	},
	{
		id: 'tpl-2',
		styleId: 'style-1',
		title: '日光通透',
		desc: '自然日光、轻盈通透',
		coverUrl: 'https://images.unsplash.com/photo-1500336624523-d727130c3328?auto=format&fit=crop&w=600&q=80',
		tags: ['自然', '清新'],
		subTemplates: [
			{
				id: 'sub-2-1',
				title: '白墙日光',
				thumbUrl: 'https://images.unsplash.com/photo-1524502397800-2eeaad7c3fe5?auto=format&fit=crop&w=400&q=80'
			},
			{
				id: 'sub-2-2',
				title: '午后阳台',
				thumbUrl: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=400&q=80'
			}
		]
	},
	{
		id: 'tpl-3',
		styleId: 'style-2',
		title: '都市电影感',
		desc: '低饱和、质感光影',
		coverUrl: 'https://images.unsplash.com/photo-1524502397800-2eeaad7c3fe5?auto=format&fit=crop&w=600&q=80',
		tags: ['低饱和', '质感'],
		subTemplates: [
			{
				id: 'sub-3-1',
				title: '街头光影',
				thumbUrl: 'https://images.unsplash.com/photo-1504703395950-b89145a5425b?auto=format&fit=crop&w=400&q=80'
			},
			{
				id: 'sub-3-2',
				title: '室内暗调',
				thumbUrl: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=400&q=80'
			}
		]
	},
	{
		id: 'tpl-4',
		styleId: 'style-2',
		title: '夜色霓虹',
		desc: '高对比、夜景氛围',
		coverUrl: 'https://images.unsplash.com/photo-1504703395950-b89145a5425b?auto=format&fit=crop&w=600&q=80',
		tags: ['夜景', '酷感'],
		subTemplates: [
			{
				id: 'sub-4-1',
				title: '霓虹街景',
				thumbUrl: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=400&q=80'
			}
		]
	},
	{
		id: 'tpl-5',
		styleId: 'style-3',
		title: '胶片复古',
		desc: '颗粒感与复古色彩',
		coverUrl: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80',
		tags: ['颗粒', '胶片'],
		subTemplates: [
			{
				id: 'sub-5-1',
				title: '复古街拍',
				thumbUrl: 'https://images.unsplash.com/photo-1500336624523-d727130c3328?auto=format&fit=crop&w=400&q=80'
			}
		]
	},
	{
		id: 'tpl-6',
		styleId: 'style-4',
		title: '雾感氛围',
		desc: '轻雾、梦幻、柔化',
		coverUrl: 'https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?auto=format&fit=crop&w=600&q=80',
		tags: ['雾感', '梦幻'],
		subTemplates: [
			{
				id: 'sub-6-1',
				title: '晨雾人像',
				thumbUrl: 'https://images.unsplash.com/photo-1524502397800-2eeaad7c3fe5?auto=format&fit=crop&w=400&q=80'
			}
		]
	}
]

function buildTemplateMap(items) {
	const map = {}
	for (const item of items) {
		map[item.id] = item
	}
	return map
}

const templateMap = buildTemplateMap(templates)

const sampleResults = [
	'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1200&q=80',
	'https://images.unsplash.com/photo-1500336624523-d727130c3328?auto=format&fit=crop&w=1200&q=80',
	'https://images.unsplash.com/photo-1524502397800-2eeaad7c3fe5?auto=format&fit=crop&w=1200&q=80'
]

export { styles, templates, templateMap, banners, sampleResults }
