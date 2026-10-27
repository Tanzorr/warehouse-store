export interface InfoPage {
  id: string | null;
  title: string;
  content: string;
}

export type CreateInfoPage = Omit<InfoPage, 'id'> | null | undefined;
