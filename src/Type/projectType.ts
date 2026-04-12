export type Project = {
    id: number;
    title: string;
    slug: string;
    hero: string;
    order: number;
    gif?: string;
    info?: string;
    layout: "grid" | "column";
    description?: string;
    images: ProjectImage[];
};

export type Projects = Project[];

export type ProjectImageItem = {
    id: number;
    src: string;
};

export type ProjectTextParagraph = {
    title: string;
    text: string;
    textHtml?: string;
};

export type ProjectTextBlock = {
    subtitle: string;
    title: string;
    paragraphs: ProjectTextParagraph[];
    textColor: string;
    backgroundColor: string;
};

export type ProjectImageBlock = {
    id: number;
    type: "textImageBlock";
    text: ProjectTextBlock;
    layout: "text-right" | "text-left";
    imagePosition?: "bottom-right";
    image: ProjectImageItem;
};

export type ProjectImage = ProjectImageItem | ProjectImageBlock;
